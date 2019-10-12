<?php

namespace Snex\Config;

class ConfigLoader
{
    protected $drivers = [];

    public function __construct(array $drivers = null)
    {
        if (is_null($drivers)) {
            $this->drivers = [
                new Driver\PHPConfigDriver(),
                new Driver\JSONConfigDriver(),
                new Driver\YAMLConfigDriver(),
            ];
        } else {
            $this->drivers = $drivers;
        }
    }

    public function loadFile(string $filePath) : Config
    {
        foreach ($this->drivers as $driver) {
            if ($driver->supportsFile($filePath)) {
                return $driver->loadFile($filePath);
            }
        }

        return null;
    }
}
