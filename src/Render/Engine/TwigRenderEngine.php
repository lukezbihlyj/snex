<?php

namespace Snex\Render\Engine;

use Twig\Environment;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Snex\Application;
use Snex\Service\ServiceInterface;
use Snex\Config\Config;
use Snex\Render\Twig\FilesystemAliasLoader;

class TwigRenderEngine implements EngineInterface, ServiceInterface
{
    protected $twigLoader;
    protected $twigEnvironment;

    public function __construct(Application $app, Config $config)
    {
        $this->twigLoader = new ChainLoader([
            new FilesystemAliasLoader($config->get('render.template_aliases')),
            new FilesystemLoader($config->get('render.template_path'))
        ]);

        $environmentConfig = $config->get('render.twig.environment_config');

        if ($app->inDebugMode()) {
            $environmentConfig['debug'] = true;
            $environmentConfig['cache'] = false;
        }

        $this->twigEnvironment = new Environment($this->twigLoader, $environmentConfig);
    }

    public function render(string $template, array $parameters = []) : string
    {
        return $this->twigEnvironment->render($template, $parameters);
    }
}
