<?php

namespace Snex\Asset\Twig;

use Snex\Asset\AssetFactory;
use Assetic\Extension\Twig\AsseticNode;
use Assetic\Contracts\Asset\AssetInterface;
use Twig\Compiler;
use Twig\Node\Node;

class AssetNode extends AsseticNode
{
    protected AssetFactory $factory;

    public function __construct(AssetFactory $factory, AssetInterface $asset, Node $body, array $inputs, array $filters, $name, array $attributes = [], $lineno = 0, $tag = null)
    {
        parent::__construct($asset, $body, $inputs, $filters, $name, $attributes, $lineno, $tag);

        $this->factory = $factory;
    }

    protected function compileAssetUrl(Compiler $compiler, AssetInterface $asset, $name) : void
    {
        $cacheBusterHash = '00000000';
        $realPath = $this->factory->getTargetPath() . '/' . $asset->getTargetPath();

        if (file_exists($realPath)) {
            $cacheBusterHash = substr(sha1_file($realPath), 0, 8);
        }

        $exposedPath = str_replace($this->factory->getDocumentRoot(), '', realpath($this->factory->getTargetPath()));
        $targetPath = $exposedPath . '/' . $asset->getTargetPath() . '?' . $cacheBusterHash;

        if (!$vars = $asset->getVars()) {
            $compiler->repr($targetPath);
            return;
        }

        $compiler->raw('strtr(')
            ->string($targetPath)
            ->raw(', array(');

        $first = true;

        foreach ($vars as $var) {
            if (!$first) {
                $compiler->raw(', ');
            }

            $first = false;

            $compiler->string('{' . $var . '}')
                ->raw(' => $context[\'assetic\'][\'vars\'][\'' . $var . '\']');
        }

        $compiler->raw('))');
    }
}
