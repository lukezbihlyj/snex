<?php

namespace Snex\Router;

use Snex\ProviderInterface;
use Snex\Application;
use Snex\Config\Config;

class RouterProvider implements ProviderInterface
{
    public function register(Application $app) : void
    {
        $config = new Config([
            'router.routes' => [],
            'router.controller_not_found' => null,
            'router.controller_exception' => null,
        ]);

        $app->config()->merge($config);
    }

    public function init(Application $app) : void
    {
        $router = $app->services()->registerAndGet('Snex\Router\Router');

        foreach ($app->config()->get('router.routes') as $routeName => $routeData) {
            $router->addRoute($routeName, $routeData);
        }
    }
}
