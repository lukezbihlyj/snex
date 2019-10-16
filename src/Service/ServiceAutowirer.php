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

    public function newAutowired(string $class, array $parameters = [])
    {
        $reflection = $this->getReflectionForClass($class);

        if ($reflection->hasMethod('__construct')) {
            $parameters = $this->autowireMethodParameters($reflection, '__construct', $parameters);

            return $reflection->newInstanceArgs($parameters);
        }

        return $reflection->newInstance();
    }

    public function callAutowired(object $instance, string $method, array $parameters = [])
    {
        $reflection = $this->getReflectionForInstance($instance);

        if (!$reflection->hasMethod($method)) {
            throw new Exception\InvalidMethodException($method);
        }

        $reflectionMethod = $reflection->getMethod($method);
        $parameters = $this->autowireMethodParameters($reflection, $method, $parameters);

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

    protected function autowireMethodParameters(ReflectionClass $reflection, string $method, array $parameters = []) : array
    {
        $mappedParameters = [];
        $reflectionMethod = $reflection->getMethod($method);
        $methodParams = $reflectionMethod->getParameters();

        foreach ($methodParams as $parameter) {
            $parameterName = $parameter->name;
            $parameterClass = $parameter->getClass();

            if (isset($parameters[$parameterName])) {
                $mappedParameters[] = $parameters[$parameterName];
                continue;
            }

            if ($parameterClass && isset($parameters[$parameterClass->name])) {
                $mappedParameters[] = $parameters[$parameterClass->name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $mappedParameters[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->isArray()) {
                $mappedParameters[] = [];
                continue;
            }

            if ($parameterClass && $parameterClass->isInstantiable()) {
                $mappedParameters[] = $this->serviceContainer->get($parameterClass->name);
                continue;
            }
        }

        return $mappedParameters;
    }
}
