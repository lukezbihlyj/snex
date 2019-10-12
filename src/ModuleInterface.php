<?php

namespace Snex;

interface ModuleInterface
{
    public function getConfigFile() : ?string;
    public function register(Application $app) : void;
    public function init(Application $app) : void;
}
