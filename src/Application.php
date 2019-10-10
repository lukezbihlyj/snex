<?php

namespace Snex;

use Snex\Config\Config;
use Snex\Config\ConfigProvider;
use Snex\Error\ErrorHandlerProvider;
use DI\Container as ServiceContainer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Application
{
    /**
     * @var string
     */
    protected $rootPath;

    /**
     * @var string
     */
    protected $localConfigFile;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var string
     */
    protected $stage = 'prod';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ServiceContainer
     */
    protected $serviceContainer;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var ProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var ModuleInterface[]
     */
    protected $modules = [];

    /**
     * Create a new instance of our application with the defined directory
     * as the root
     */
    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim(realpath($rootPath), '/');

        return $this;
    }

    /**
     * Initialize all of the internal components, including services, the
     * dependency injection container, routing, etc
     */
    public function init(array $modules = []) : void
    {
        $this->config = new Config();
        $this->serviceContainer = new ServiceContainer();
        $this->eventDispatcher = new EventDispatcher();

        $this->addProvider(new ConfigProvider());
        $this->addProvider(new ErrorHandlerProvider());

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
     * Get the local config file path, if set
     */
    public function getLocalConfigFile() : string
    {
        return $this->localConfigFile;
    }

    /**
     * Set the local config file path, which is used as the final override
     * to any existing configuration flags
     */
    public function setLocalConfigFile(string $localConfigFile) : void
    {
        $this->localConfigFile = $localConfigFile;
    }

    /**
     * If this application is running in debug mode
     */
    public function inDebugMode() : bool
    {
        return $this->debug;
    }

    /**
     * Update to make sure the application is running in the right mode
     */
    public function setInDebugMode(bool $debug) : void
    {
        $this->debug = $debug;
    }

    /**
     * Return the current stage this application is running in
     */
    public function getStage() : string
    {
        return $this->stage;
    }

    /**
     * Update to make sure the application is running in the right stage
     */
    public function setStage(string $stage) : void
    {
        $this->stage = $stage;
    }

    /**
     * Get the current configuration container
     */
    public function getConfigContainer(string $key = null, $default = null) : Config
    {
        if (!is_null($key)) {
            return $this->config->get($key, $default);
        }

        return $this->config;
    }

    /**
     * Return a single configuration element with the given key or the default
     * if the key was not found
     */
    public function getConfig(string $key = null, $default = null)
    {
        return $this->config->get($key, $default);
    }

    /**
     * Get the main service container
     */
    public function getServiceContainer() : ServiceContainer
    {
        return $this->serviceContainer;
    }

    /**
     * Get one of the services from the service container
     */
    public function getService(string $serviceName) : ServiceInterface
    {
        return $this->serviceContainer->get($serviceName);
    }

    /**
     * Get the event dispatcher
     */
    public function getEventDispatcher() : EventDispatcher
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
    }

    /**
     * Handle a HTTP request and send back a response
     */
    public function handleRequest() : void
    {
        $request = Request::createFromGlobals();

        $response = new Response(json_encode([
            'status' => 'success',
            'config' => $this->config->all()
        ]), 200, [
            'content-type' => 'application/json'
        ]);

        $response->prepare($request);
        $response->send();
    }

    /**
     * Handle a CLI request and execute the relevant commands
     */
    public function handleConsole() : void
    {
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
