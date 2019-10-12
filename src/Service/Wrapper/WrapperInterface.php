<?php

namespace Snex\Service\Wrapper;

use Snex\Service\ServiceInterface;

interface WrapperInterface
{
    public function __invoke() : ServiceInterface;
}
