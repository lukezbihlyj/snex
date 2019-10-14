<?php

namespace Snex\Service\Wrapper;

class ObjectWrapper implements WrapperInterface
{
    protected $wrapped;

    public function __construct($wrapped)
    {
        $this->wrapped = $wrapped;
    }

    public function __invoke()
    {
        return $this->wrapped;
    }
}
