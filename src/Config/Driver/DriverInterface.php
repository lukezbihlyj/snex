<?php

namespace Snex\Config\Driver;

use Snex\Config\Config;

interface DriverInterface
{
    public function supportsFile($filePath) : bool;
    public function loadFile($filePath) : Config;
}
