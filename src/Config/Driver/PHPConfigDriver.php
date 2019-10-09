<?php

namespace Snex\Config\Driver;

use Snex\Config\Config;

class PHPConfigDriver implements DriverInterface
{
    public function supportsFile($filePath) : bool
    {
        return preg_match('#\.php(\.dist)?$#', $filePath) === 1;
    }

    public function loadFile($filePath) : Config
    {
        $data = require $filePath;

        if ($data === 1 || !is_array($data)) {
            return null;
        }

        return new Config($data);
    }
}
