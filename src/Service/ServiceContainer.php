<?php

namespace Snex\Service;

class ServiceContainer
{
    protected $services = [];
    protected $instances = [];

    public function has(string $name) : bool
    {
        return isset($this->services[$name]);
    }

    public function get(string $name) : ?ServiceInterface
    {
        if (!isset($this->services[$name])) {
            return null;
        }

        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if ($this->services[$name] instanceof Wrapper\FactoryWrapper) {
            return $this->services[$name]();
        }

        $this->instances[$name] = $this->services[$name]();

        return $this->instances[$name];
    }

    public function register(string $name, $service = null) : void
    {
        if (isset($this->services[$name])) {
            throw new Exception\DuplicateServiceException($name);
        }

        if (is_null($service)) {
            $service = $name;
        }

        if (!($service instanceof Wrapper\WrapperInterface)) {
            $service = $this->wrapService($service);
        }

        $this->services[$name] = $service;
    }

    public function registerFactory(string $name, $service = null) : void
    {
        if (is_null($service)) {
            $service = $name;
        }

        $service = new Wrapper\FactoryWrapper($this->wrapService($service));

        $this->register($name, $service);
    }

    protected function wrapService($service) : Wrapper\WrapperInterface
    {
        if (is_string($service)) {
            return new Wrapper\ClosureWrapper(function () use ($service) {
                return new $service;
            });
        } elseif ($service instanceof ServiceInterface) {
            $wrapped = new Wrapper\ObjectWrapper($service);
        }

        throw new Exception\InvalidServiceException($name);
    }
}
