<?php

namespace Snex\Render\Twig;

use Twig\LoaderInterface;
use Twig\ExistsLoaderInterface;
use Twig\Error\LoaderError;
use Twig\Source;

class TwigTemplateLoader implements LoaderInterface, ExistsLoaderInterface
{
    protected $aliases = [];

    public function __construct(array $aliases = [])
    {
        $this->aliases = $aliases;

        return $this;
    }

    public function getSourceContext(string $name) : Source
    {
        $name = (string) $name;

        if (!isset($this->aliases[$name])) {
            throw new LoaderError('Template "' . $name . '" is not defined.');
        }

        if (!file_exists($this->aliases[$name])) {
            throw new LoaderError('Unable to find template "' . $name . '".');
        }

        return new Source(file_get_contents($this->aliases[$name]), $name);
    }

    public function exists(string $name) : bool
    {
        return isset($this->aliases[(string) $name]);
    }

    public function getCacheKey(string $name) : string
    {
        $name = (string) $name;

        if (!isset($this->aliases[$name])) {
            throw new LoaderError('Template "' . $name . '" is not defined.');
        }

        return $this->aliases[$name];
    }

    public function isFresh(string $name, int $time) : bool
    {
        $name = (string) $name;

        if (!isset($this->aliases[$name])) {
            throw new LoaderError('Template "' . $name . '" is not defined.');
        }

        return true;
    }
}
