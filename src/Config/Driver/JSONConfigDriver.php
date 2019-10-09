<?php

namespace Snex\Config\Driver;

use Snex\Config\Config;

class JSONConfigDriver implements DriverInterface
{
    public function supportsFile($filePath) : bool
    {
        return preg_match('#\.json(\.dist)?$#', $filePath) === 1;
    }

    public function loadFile($filePath) : Config
    {
        $data = file_get_contents($filePath);
        $data = json_decode($data, true);

        if (!is_array($data)) {
            return null;
        }

        return new Config($data);
    }
}
