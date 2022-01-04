<?php

namespace Snex\Router;

use Snex\Service\ServiceInterface;
use Snex\Application;
use Snex\Config\Config;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Router implements ServiceInterface
{
    protected Application $app;
    protected RouteCollection $routeCollection;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->routeCollection = new RouteCollection();
    }

    public function match(Request $request) : array
    {
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);

        $matcher = new UrlMatcher($this->routeCollection, $requestContext);

        return $matcher->matchRequest($request);
    }

    public function execute(Request $request) : Response
    {
        try {
            $routeData = $this->match($request);
        } catch (ResourceNotFoundException $e) {
            $routeData = $this->getRouteNotFoundData();
        } catch (Exception $e) {
            if ($this->app->inDebugMode()) {
                throw $e;
            }

            $routeData = $this->getRouteExceptionData();
        }

        try {
            $autowirer = $this->app->services()->getAutowirer();
            $routeController = $autowirer->newAutowired($routeData['controller']);

            $response = $autowirer->callAutowired($routeController, $routeData['action'], [
                'request' => $request
            ]);
        } catch (Exception $e) {
            if ($this->app->inDebugMode()) {
                throw $e;
            }

            $response = new Response(null, 500);
        }

        if (!($response instanceof Response)) {
            $response = new Response($response, isset($routeData['status_code']) ? $routeData['status_code'] : 200);
        }

        return $response;
    }

    public function addRoute(string $name, array $routeData) : void
    {
        list($controller, $action) = explode('::', $routeData['controller']);

        $route = new Route(
            $routeData['pattern'], [
                'controller' => $controller,
                'action' => $action
            ],
            isset($routeData['assert']) ? $routeData['assert'] : [],
            [],
            null,
            null,
            $routeData['method'],
            null
        );

        $this->routeCollection->add($name, $route);
    }

    protected function getRouteNotFoundData() : array
    {
        $controller = explode('::', $this->app->config()->get('router.controller_not_found', 'Snex\Controller\ErrorController::notFound'));

        return [
            'controller' => $controller[0],
            'action' => $controller[1],
            'status_code' => 404,
        ];
    }

    protected function getRouteExceptionData() : array
    {
        $controller = explode('::', $this->app->config()->get('router.controller_exception', 'Snex\Controller\ErrorController::exception'));

        return [
            'controller' => $controller[0],
            'action' => $controller[1],
            'status_code' => 500,
        ];
    }
}
