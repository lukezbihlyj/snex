<?php

namespace Snex\Asset\Twig;

use Snex\Application;
use Snex\Asset\AssetFactory;
use Twig\Extension\AbstractExtension;

class AssetExtension extends AbstractExtension
{
    protected Application $app;
    protected AssetFactory $factory;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->factory = $app->services()->get('Snex\Asset\AssetFactory');
    }

    public function getName() : string
    {
        return 'asset';
    }

    public function getTokenParsers() : array
    {
        return [
            new AssetTokenParser($this->factory, [
                'js' => [
                    'output' => 'js/*.js'
                ],
                'css' => [
                    'output' => 'css/*.css'
                ],
                'image' => [
                    'output' => 'image/*',
                    'single' => true
                ]
            ], 'misc/*')
        ];
    }
}
