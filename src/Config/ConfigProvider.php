<?php

namespace Snex\Config;

use Snex\ProviderInterface;
use Snex\Application;

class ConfigProvider implements ProviderInterface
{
    public function register(Application $app) : void
    {
    }

    public function init(Application $app) : void
    {
        $loader = new ConfigLoader();

        foreach ($app->getModules() as $module) {
            $configFile = $module->getConfigFile();

            if (is_null($configFile)) {
                continue;
            }

            $config = $loader->loadFile($configFile);

            if (!$config) {
                continue;
            }

            $app->getConfig()->merge($config);
        }

        if ($app->getLocalConfigFile()) {
            $configFile = $app->getLocalConfigFile();
            $config = $loader->loadFile($configFile);

            if ($config) {
                $app->getConfig()->merge($config);
            }
        }

        if ($app->getConfig('debug')) {
            $app->setInDebugMode(true);
        }

        if ($app->getConfig('stage')) {
            $app->setStage($app->getConfig('stage'));
        }
    }
}
