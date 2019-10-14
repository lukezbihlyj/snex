<?php

namespace Snex\Service\Wrapper;

use ReflectionClass;
use Snex\Service\ServiceContainer;

class NewWrapper implements WrapperInterface
{
    protected $serviceContainer;
    protected $wrapped;
    protected $parameters = [];

    protected $cachedReflections = [];

    public function __construct(ServiceContainer $serviceContainer, string $wrapped, array $parameters = [])
    {
        $this->serviceContainer = $serviceContainer;
        $this->wrapped = $wrapped;
        $this->parameters = $parameters;
    }

    public function __invoke()
    {
        $reflection = $this->getReflectionForClass($this->wrapped);

        if ($reflection->hasMethod('__construct')) {
            $this->autowireParameters($this->wrapped);

            return $reflection->newInstanceArgs($this->parameters);
        }

        return $reflection->newInstance();
    }

    protected function getReflectionForClass(string $class) : ReflectionClass
    {
        if (isset($this->cachedReflections[$class])) {
            return $this->cachedReflections[$class];
        }

        return $this->cachedReflections[$class] = new ReflectionClass($class);
    }

    protected function autowireParameters(string $class) : void
    {
        $reflection = $this->getReflectionForClass($this->wrapped);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return;
        }

        $constructorParams = $constructor->getParameters();

        foreach ($constructorParams as $parameter) {
            $parameterName = $parameter->name;

            if (isset($this->parameters[$parameterName])) {
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $this->parameters[$parameterName] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->isArray()) {
                $this->parameters[$parameterName] = [];
                continue;
            }

            $parameterClass = $parameter->getClass();

            if ($parameterClass && $parameterClass->isInstantiable()) {
                $this->parameters[$parameterName] = $this->serviceContainer->get($parameterClass->name);
                continue;
            }
        }
    }
}
