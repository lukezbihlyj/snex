<?php

namespace Snex\Asset;

use Snex\Application;
use Assetic\Factory\AssetFactory as BaseAssetFactory;

class AssetFactory extends BaseAssetFactory
{
    protected Application $app;

    public function __construct(Application $app, bool $debug = false)
    {
        parent::__construct($app->config()->get('asset.source_path'), $debug);

        $this->app = $app;
    }

    public function getRootPath() : string
    {
        return $this->app->getRootPath();
    }

    public function getSourcePath() : string
    {
        return $this->app->config()->get('asset.source_path');
    }

    public function getTargetPath() : string
    {
        return $this->app->config()->get('asset.target_path');
    }

    public function getDocumentRoot() : string
    {
        return $this->app->services()->get('Symfony\Component\HttpFoundation\Request')
            ->server
            ->get('DOCUMENT_ROOT', $this->getRootPath());
    }
}
