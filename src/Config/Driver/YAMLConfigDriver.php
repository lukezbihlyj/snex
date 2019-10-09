<?php

namespace Snex\Config\Driver;

use Snex\Config\Config;
use Symfony\Component\Yaml\Yaml;

class YAMLConfigDriver implements DriverInterface
{
    public function supportsFile($filePath) : bool
    {
        return preg_match('#\.ya?ml(\.dist)?$#', $filePath) === 1;
    }

    public function loadFile($filePath) : Config
    {
        $data = Yaml::parse($filePath);

        if (!is_array($data)) {
            return null;
        }

        return new Config($data);
    }
}
