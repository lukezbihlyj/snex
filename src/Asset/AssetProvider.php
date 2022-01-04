<?php

namespace Snex\Asset;

use Snex\ProviderInterface;
use Snex\Application;
use Snex\Config\Config;
use Assetic\AssetManager;
use Assetic\FilterManager;
use Assetic\Filter\LessphpFilter;
use Assetic\Filter\CssMinFilter;
use Assetic\Filter\JSqueezeFilter;

class AssetProvider implements ProviderInterface
{
    public function register(Application $app) : void
    {
        $config = new Config([
            'asset.source_path' => null,
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
            $filterManager->set('jsmin', new JShrinkFilter());

            $factory = new AssetFactory($app->config()->get('asset.source_path'));
            $factory->setAssetManager($assetManager);
            $factory->setFilterManager($filterManager);
            $factory->setDefaultOutput('misc/*');

            return $factory;
        });
    }
}