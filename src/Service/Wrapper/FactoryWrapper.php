<?php

namespace Snex\Service\Wrapper;

class FactoryWrapper implements WrapperInterface
{
    protected $wrapped;

    public function __construct(WrapperInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    public function __invoke()
    {
        return $this->wrapped();
    }
}
