<?php

namespace Snex\Render;

use Snex\ProviderInterface;
use Snex\Application;
use Snex\Config\Config;

class RenderEngineProvider implements ProviderInterface
{
    public function register(Application $app) : void
    {
        $config = new Config([
            'render.template_path' => null,
        ]);

        $app->config()->merge($config);
    }

    public function init(Application $app) : void
    {
        $enabledEngines = $app->config()->get('render.enabled_engines', [
            'Snex\Render\Engine\TwigRenderEngine'
        ]);

        foreach ($enabledEngines as $engineClass) {
            $app->services()->register($engineClass);
        }
    }
}
