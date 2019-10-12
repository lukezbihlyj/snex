<?php

namespace Snex\Config\Driver;

use Snex\Config\Config;

interface DriverInterface
{
    public function supportsFile(string $filePath) : bool;
    public function loadFile(string $filePath) : Config;
}
