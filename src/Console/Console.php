<?php

namespace Snex\Console;

use Snex\Application;
use Symfony\Component\Console\Application as BaseConsole;

class Console extends BaseConsole
{
    protected Application $app;

    public function __construct(Application $app)
    {
        parent::__construct($app->config()->get('name', 'UNKNOWN'), $app->config()->get('version', 'UNKNOWN'));

        $this->app = $app;
    }
}
