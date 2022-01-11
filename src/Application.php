<?php

namespace Snex;

use Exception;
use Snex\Asset\AssetProvider;
use Snex\Config\Config;
use Snex\Config\ConfigProvider;
use Snex\Console\Console;
use Snex\Error\ErrorHandlerProvider;
use Snex\Event\EventDispatcher;
use Snex\Render\RenderProvider;
use Snex\Router\RouterProvider;
use Snex\Service\ServiceContainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Application
{
    protected string $rootPath;
    protected string $localConfigFile;
    protected bool $hasInitialized = false;
    protected Config $config;
    protected ServiceContainer $serviceContainer;
    protected EventDispatcher $eventDispatcher;
    protected array $providers = [];
    protected array $modules = [];

    /**
     * Create a new instance of our application with the defined directory
     * as the root
     */
    public function __construct(string $rootPath, string $localConfigFile)
    {
        $this->rootPath = rtrim(realpath($rootPath), '/');
        $this->localConfigFile = $this->rootPath . '/' . $localConfigFile;
    }

    /**
     * Initialize all of the internal components, including services, the
     * dependency injection container, routing, etc
     */
    public function init(array $modules = []) : void
    {
        $app = $this;

        // We put in a small hack here to hardcode this override on the IP addresses
        // otherwise we have a bunch of problems dealing with Symfony request headers
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        $this->config = new Config();
        $this->serviceContainer = new ServiceContainer();
        $this->eventDispatcher = new EventDispatcher();

        $this->serviceContainer->register('Snex\Application', $this);
        $this->serviceContainer->register('Snex\Config\Config', $this->config);
        $this->serviceContainer->register('Snex\Event\EventDispatcher', $this->eventDispatcher);
        $this->serviceContainer->register('Snex\Service\ServiceContainer', $this->serviceContainer);

        $this->serviceContainer->register('Symfony\Component\HttpFoundation\Request', function () use ($app) {
            Request::setTrustedProxies($app->config()->get('request.trusted_proxies', []), Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT);

            return Request::createFromGlobals();
        });

        $this->addProvider(new ConfigProvider());
        $this->addProvider(new ErrorHandlerProvider());
        $this->addProvider(new AssetProvider());
        $this->addProvider(new RenderProvider());
        $this->addProvider(new RouterProvider());

        foreach ($modules as $module) {
            $moduleClass = $module . '\\Module';

            if (!class_exists($moduleClass)) {
                continue;
            }

            $module = new $moduleClass;

            $this->addModule($module);
        }

        $this->initProviders();
        $this->initModules();

        $this->hasInitialized = true;
    }

    /**
     * If this application is being executed in the command line or as a
     * web application
     */
    public function isCli() : bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Get the application root path
     */
    public function getRootPath() : string
    {
        return $this->rootPath;
    }

    /**
     * Get the local config file path, if set
     */
    public function getLocalConfigFile() : ?string
    {
        return $this->localConfigFile;
    }

    /**
     * If this application is running in debug mode
     */
    public function inDebugMode() : bool
    {
        return $this->config->get('debug', false);
    }

    /**
     * Return the current stage this application is running in
     */
    public function getStage() : string
    {
        return $this->config->get('stage', 'prod');
    }

    /**
     * Get the current configuration container
     */
    public function config() : Config
    {
        return $this->config;
    }

    /**
     * Get the main service container
     */
    public function services() : ServiceContainer
    {
        return $this->serviceContainer;
    }

    /**
     * Get the event dispatcher
     */
    public function events() : EventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * Get all of the registered providers
     */
    public function getProviders() : array
    {
        return $this->providers;
    }

    /**
     * Add a new provider to the known chain to be initialised
     */
    public function addProvider(ProviderInterface $provider) : void
    {
        $provider->register($this);

        $this->providers[] = $provider;

        if ($this->hasInitialized) {
            $provider->init($this);
        }
    }

    /**
     * Get all of the registered modules
     */
    public function getModules() : array
    {
        return $this->modules;
    }

    /**
     * Add a new module to the known chain to be initialised
     */
    public function addModule(ModuleInterface $module) : void
    {
        $module->register($this);

        $this->modules[] = $module;

        if ($this->hasInitialized) {
            $module->init($this);
        }
    }

    /**
     * Handle the request, automatically inferring the context of the operation
     * depending on the environment
     */
    public function handle() : void
    {
        if (php_sapi_name() === 'cli') {
            $this->handleConsole();
        } else {
            $this->handleRequest();
        }
    }

    /**
     * Handle a HTTP request and send back a response
     */
    public function handleRequest() : void
    {
        $request = $this->serviceContainer->get('Symfony\Component\HttpFoundation\Request');
        $router = $this->serviceContainer->get('Snex\Router\Router');
        $response = $router->execute($request);

        if (!($response instanceof Response)) {
            $response = new Response($response);
        }

        $response->prepare($request);
        $response->send();
    }

    /**
     * Handle a CLI request and execute the relevant commands
     */
    public function handleConsole() : void
    {
        $console = new Console($this);

        foreach ($this->config->get('console.commands', []) as $command) {
            $console->add(new $command($this, $console));
        }

        $console->run();
    }

    /**
     * Initialise all of the providers that were loaded, in the correct order
     */
    protected function initProviders() : void
    {
        foreach ($this->providers as $provider) {
            $provider->init($this);
        }
    }

    /**
     * Initialise all of the modules that were loaded, in the correct order
     */
    protected function initModules() : void
    {
        foreach ($this->modules as $module) {
            $module->init($this);
        }
    }
}
