<?php

namespace Snex\Router;

use Snex\ProviderInterface;
use Snex\Application;
use Snex\Config\Config;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpFoundation\Request;

class RouterProvider implements ProviderInterface
{
    public function register(Application $app) : void
    {
        $config = new Config([
            'router.routes' => [],
        ]);

        $app->config()->merge($config);
    }

    public function init(Application $app) : void
    {
        $app->services()->register('Snex\Router\Router');
    }
}
