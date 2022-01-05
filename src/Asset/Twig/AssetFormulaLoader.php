<?php

namespace Snex\Asset\Twig;

use Assetic\Contracts\Factory\Loader\FormulaLoaderInterface;
use Assetic\Contracts\Factory\Resource\ResourceInterface;
use Assetic\Extension\Twig\AsseticNode;
use Assetic\Extension\Twig\AsseticFilterNode;
use Twig\Environment;
use Twig\Source;
use Twig\Node\Node;

class AssetFormulaLoader implements FormulaLoaderInterface
{
    protected Environment $twigEnvironment;

    public function __construct(Environment $twigEnvironment)
    {
        $this->twigEnvironment = $twigEnvironment;
    }

    public function load(ResourceInterface $resource) : array
    {
        try {
            $tokens = $this->twigEnvironment->tokenize(new Source($resource->getContent(), (string) $resource));
            $nodes = $this->twigEnvironment->parse($tokens);
        } catch (\Exception $e) {
            return [];
        }

        return $this->loadNode($nodes);
    }

    protected function loadNode(Node $node) : array
    {
        $formulae = [];

        if ($node instanceof AsseticNode) {
            $formulae[$node->getAttribute('name')] = [
                $node->getAttribute('inputs'),
                $node->getAttribute('filters'),
                [
                    'output' => $node->getAttribute('asset')->getTargetPath(),
                    'name' => $node->getAttribute('name'),
                    'debug' => $node->getAttribute('debug'),
                    'combine' => $node->getAttribute('combine'),
                    'vars' => $node->getAttribute('vars'),
                ]
            ];
        } elseif ($node instanceof AsseticFilterNode) {
            $name = $node->getAttribute('name');
            $arguments = [];

            foreach ($node->getNode('arguments') as $argument) {
                $arguments[] = eval('return ' . $this->twigEnvironment->compile($argument) . ';');
            }

            $invoker = $this->twigEnvironment->getExtension('Snex\Asset\Twig\AssetExtension')->getFilterInvoker($name);

            $inputs = isset($arguments[0]) ? (array) $arguments[0] : [];
            $filters = $invoker->getFilters();
            $options = array_replace($invoker->getOptions(), isset($arguments[1]) ? $arguments[1] : []);

            if (!isset($options['name'])) {
                $options['name'] = $invoker->getFactory()->generateAssetName($inputs, $filters, $options);
            }

            $formulae[$options['name']] = [$inputs, $filters, $options];
        }

        foreach ($node as $child) {
            if ($child instanceof Node) {
                $formulae += $this->loadNode($child);
            }
        }

        if ($node->hasAttribute('embedded_templates')) {
            foreach ($node->getAttribute('embedded_templates') as $child) {
                $formulae += $this->loadNode($child);
            }
        }

        return $formulae;
    }
}
