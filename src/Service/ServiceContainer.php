<?php

namespace Snex\Service;

use Closure;

class ServiceContainer
{
    protected $services = [];
    protected $instances = [];

    protected $aurowirer;

    public function __construct()
    {
        $this->autowirer = new ServiceAutowirer($this);
    }

    public function getAutowirer() : ServiceAutowirer
    {
        return $this->autowirer;
    }

    public function has(string $name) : bool
    {
        return isset($this->services[$name]);
    }

    public function get(string $name)
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

    public function register(string $name, $service = null, array $parameters = []) : void
    {
        if (isset($this->services[$name])) {
            throw new Exception\DuplicateServiceException($name);
        }

        if (is_null($service)) {
            $service = $name;
        }

        if (!($service instanceof Wrapper\WrapperInterface)) {
            $service = $this->wrapService($service, $parameters);
        }

        $this->services[$name] = $service;
    }

    public function registerFactory(string $name, $service = null, array $parameters = []) : void
    {
        if (is_null($service)) {
            $service = $name;
        }

        $service = new Wrapper\FactoryWrapper($this->wrapService($service, $parameters));

        $this->register($name, $service);
    }

    public function registerAndGet(string $name, $service = null, array $parameters = [])
    {
        $this->register($name, $service, $parameters);

        return $this->get($name);
    }

    protected function wrapService($service, array $parameters = []) : Wrapper\WrapperInterface
    {
        if (is_string($service)) {
            return new Wrapper\NewWrapper($this, $service, $parameters);
        } elseif ($service instanceof Closure) {
            return new Wrapper\ClosureWrapper($service);
        } elseif (is_object($service)) {
            return new Wrapper\ObjectWrapper($service);
        }

        throw new Exception\InvalidServiceException($service);
    }
}
