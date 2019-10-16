<?php

namespace Snex\Controller;

use Symfony\Component\HttpFoundation\Response;

class ErrorController implements ControllerInterface
{
    public function notFound() : Response
    {
        return new Response('Route not found', 404);
    }

    public function exception() : Response
    {
        return new Response('Exception was encountered', 500);
    }
}
