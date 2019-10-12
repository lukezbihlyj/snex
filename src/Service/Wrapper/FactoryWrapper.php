<?php

namespace Snex\Service\Wrapper;

use Closure;
use Snex\Service\ServiceInterface;

class FactoryWrapper implements WrapperInterface
{
    protected $wrapped;

    public function __construct(WrapperInterface $wrapped)
    {
        $this->wrapped = $wrapped;

        return $this;
    }

    public function __invoke() : ServiceInterface
    {
        return $this->wrapped();
    }
}
