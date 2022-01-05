<?php

namespace Snex\Asset;

use Snex\ProviderInterface;
use Snex\Application;
use Snex\Config\Config;
use Assetic\AssetManager;
use Assetic\FilterManager;
use Assetic\Filter\LessphpFilter;
use Assetic\Filter\CssMinFilter;

class AssetProvider implements ProviderInterface
{
    public function register(Application $app) : void
    {
        $config = new Config([
            'asset.source_path' => null,
            'asset.target_path' => null,

            'console.commands' => [
                'Snex\Asset\Console\AssetDumpCommand'
            ],
        ]);

        $app->config()->merge($config);
    }

    public function init(Application $app) : void
    {
        $app->services()->register('Snex\Asset\AssetFactory', function () use ($app) {
            $assetManager = new AssetManager();

            $filterManager = new FilterManager();
            $filterManager->set('less', new LessphpFilter());
            $filterManager->set('cssmin', new CssMinFilter());
            $filterManager->set('jsmin', new Filter\JShrinkFilter());

            $factory = new AssetFactory($app);
            $factory->setAssetManager($assetManager);
            $factory->setFilterManager($filterManager);
            $factory->setDefaultOutput('misc/*');

            return $factory;
        });
    }
}
