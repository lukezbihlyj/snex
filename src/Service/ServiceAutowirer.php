<?php

namespace Snex\Service;

use ReflectionClass;

class ServiceAutowirer
{
    protected $serviceContainer;

    protected $cachedReflections = [];

    public function __construct(ServiceContainer $serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }

    public function newAutowired(string $class)
    {
        $reflection = $this->getReflectionForClass($class);

        if ($reflection->hasMethod('__construct')) {
            $parameters = $this->autowireMethodParameters($reflection, '__construct');

            return $reflection->newInstanceArgs($parameters);
        }

        return $reflection->newInstance();
    }

    public function callAutowired(object $instance, string $method)
    {
        $reflection = $this->getReflectionForInstance($instance);

        if (!$reflection->hasMethod($method)) {
            throw new Exception\InvalidMethodException($method);
        }

        $reflectionMethod = $reflection->getMethod($method);
        $parameters = $this->autowireMethodParameters($reflection, $method);

        return $reflectionMethod->invokeArgs($instance, $parameters);
    }

    protected function getReflectionForClass(string $class) : ReflectionClass
    {
        if (isset($this->cachedReflections[$class])) {
            return $this->cachedReflections[$class];
        }

        return $this->cachedReflections[$class] = new ReflectionClass($class);
    }

    protected function getReflectionForInstance(object $instance) : ReflectionClass
    {
        return $this->getReflectionForClass(get_class($instance));
    }

    protected function autowireMethodParameters(ReflectionClass $reflection, string $method) : array
    {
        $parameters = [];

        $reflectionMethod = $reflection->getMethod($method);
        $methodParams = $reflectionMethod->getParameters();

        foreach ($methodParams as $parameter) {
            $parameterName = $parameter->name;

            if (isset($parameters[$parameterName])) {
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $parameters[$parameterName] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->isArray()) {
                $parameters[$parameterName] = [];
                continue;
            }

            $parameterClass = $parameter->getClass();

            if ($parameterClass && $parameterClass->isInstantiable()) {
                $parameters[$parameterName] = $this->serviceContainer->get($parameterClass->name);
                continue;
            }
        }

        return $parameters;
    }
}
