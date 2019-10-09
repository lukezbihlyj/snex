<?php

namespace Snex;

interface ProviderInterface
{
    public function register(Application $app) : void;
    public function init(Application $app) : void;
}
