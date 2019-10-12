<?php

namespace Snex\Service\Wrapper;

use Snex\Service\ServiceInterface;

class ObjectWrapper implements WrapperInterface
{
    protected $wrapped;

    public function __construct(ServiceInterface $wrapped)
    {
        $this->wrapped = $wrapped;

        return $this;
    }

    public function __invoke() : ServiceInterface
    {
        return $this->wrapped;
    }
}
