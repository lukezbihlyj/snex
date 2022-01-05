<?php

namespace Snex\Console;

use Snex\Application;
use Symfony\Component\Console\Command\Command as BaseCommand;

class ConsoleCommand extends BaseCommand
{
    protected Application $app;
    protected Console $console;

    public function __construct(Application $app, Console $console)
    {
        parent::__construct();

        $this->app = $app;
        $this->console = $console;
    }
}
