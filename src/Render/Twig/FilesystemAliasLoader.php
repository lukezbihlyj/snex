<?php

namespace Snex\Render\Twig;

use Twig\Source;
use Twig\Loader\LoaderInterface;
use Twig\Error\LoaderError;

class FilesystemAliasLoader implements LoaderInterface
{
    protected array $aliases = [];

    public function __construct(array $aliases = [])
    {
        $this->aliases = $aliases;
    }

    public function getSourceContext($name) : Source
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

    public function getCacheKey($name) : string
    {
        $name = (string) $name;

        if (!isset($this->aliases[$name])) {
            throw new LoaderError('Template "' . $name . '" is not defined.');
        }

        return $this->aliases[$name];
    }

    public function isFresh($name, $time) : bool
    {
        $name = (string) $name;

        if (!isset($this->aliases[$name])) {
            throw new LoaderError('Template "' . $name . '" is not defined.');
        }

        return true;
    }

    public function exists($name) : bool
    {
        return isset($this->aliases[(string) $name]);
    }
}
