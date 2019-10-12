<?php

namespace Snex\Service\Wrapper;

use Closure;
use Snex\Service\ServiceInterface;

class ClosureWrapper implements WrapperInterface
{
    protected $wrapped;

    public function __construct(Closure $wrapped)
    {
        $this->wrapped = $wrapped;

        return $this;
    }

    public function __invoke() : ServiceInterface
    {
        return $this->wrapped->call($this);
    }
}
