<?php

namespace Snex\Config;

class ConfigLoader
{
    /**
     * @var DriverInterface[]
     */
    protected $drivers = [];

    /**
     * Create a new instance of the loader, and set up the supported drivers as
     * requested by the application or the defaults
     */
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

    /**
     * Load a file from disk and parse whatever format it's in. Currently supported
     * drivers:
     *
     * - PHPConfigDriver
     * - JSONConfigDriver
     * - YAMLConfigDriver
     */
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
