<?php

namespace Snex\Render;

use Snex\Service\ServiceInterface;
use Snex\Application;
use Snex\Config\Config;

class Renderer implements ServiceInterface
{
    protected array $engineClasses = [
        'twig' => 'Snex\Render\Engine\TwigRenderEngine',
    ];

    protected Application $app;
    protected string $defaultEngine;

    public function __construct(Application $app, Config $config)
    {
        $this->app = $app;
        $this->defaultEngine = $config->get('render.default_engine', 'twig');
    }

    public function render(string $name, array $parameters = [], string $engine = null) : ?string
    {
        if (is_null($engine)) {
            $engine = $this->defaultEngine;
        }

        $engineClass = $this->getClassForEngine($engine);

        if (is_null($engineClass)) {
            throw new Exception\MissingEngineException($engine);
        }

        $engine = $this->app->services()->get($engineClass);

        if (is_null($engine)) {
            throw new Exception\MissingEngineException($engineClass);
        }

        return $engine->render($name, $parameters);
    }

    public function getClassForEngine(string $engine) : ?string
    {
        return isset($this->engineClasses[$engine]) ? $this->engineClasses[$engine] : null;
    }
}
