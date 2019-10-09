<?php

namespace Snex\Error;

use Snex\ProviderInterface;
use Snex\Application;
use Whoops\Run as WhoopsRun;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;

class ErrorHandlerProvider implements ProviderInterface
{
    public function register(Application $app) : void
    {
    }

    public function init(Application $app) : void
    {
        if (!$app->inDebugMode()) {
            return;
        }

        $whoops = new WhoopsRun;

        if ($app->isCli()) {
            $whoops->prependHandler(new PlainTextHandler());
        } else {
            $whoops->prependHandler(new PrettyPageHandler());
        }

        $whoops->register();
    }
}
