<?php

namespace Snex\Render;

use Snex\ProviderInterface;
use Snex\Application;
use Snex\Config\Config;

class RenderProvider implements ProviderInterface
{
    public function register(Application $app) : void
    {
        $config = new Config([
            'render.template_path' => null,
            'render.template_aliases' => [],

            'render.twig.environment_config' => [
                'debug' => false,
                'cache' => false,
            ],
        ]);

        $app->config()->merge($config);
    }

    public function init(Application $app) : void
    {
        $enabledEngines = $app->config()->get('render.enabled_engines', ['twig']);

        $renderer = $app->services()->registerAndGet('Snex\Render\Renderer');

        foreach ($enabledEngines as $engineClass) {
            $app->services()->register($renderer->getClassForEngine($engineClass));
        }
    }
}
