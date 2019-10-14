<?php

namespace Snex\Service\Wrapper;

use Closure;

class ClosureWrapper implements WrapperInterface
{
    protected $wrapped;

    public function __construct(Closure $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    public function __invoke()
    {
        return $this->wrapped->call($this);
    }
}
