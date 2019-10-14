<?php

namespace Snex\Router;

use Snex\Service\ServiceInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpFoundation\Request;

class Router implements ServiceInterface
{
    protected $routeCollection;

    public function __construct()
    {
        $this->routeCollection = new RouteCollection();
        $this->routeCollection->add('/bye', new Route('/bye', [], [], [], null, [], ['get']));
    }

    public function match(Request $request) : array
    {
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);

        $matcher = new UrlMatcher($this->routeCollection, $requestContext);

        return $matcher->matchRequest($request);
    }
}
