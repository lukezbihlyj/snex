<?php

namespace Snex\Service\Wrapper;

use ReflectionClass;
use Snex\Service\ServiceContainer;

class NewWrapper implements WrapperInterface
{
    protected $serviceContainer;
    protected $wrapped;
    protected $parameters = [];

    public function __construct(ServiceContainer $serviceContainer, string $wrapped, array $parameters = [])
    {
        $this->serviceContainer = $serviceContainer;
        $this->wrapped = $wrapped;
        $this->parameters = $parameters;
    }

    public function __invoke()
    {
        return $this->serviceContainer->getAutowirer()->newAutowired($this->wrapped);
    }
}
