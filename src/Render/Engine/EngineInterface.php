<?php

namespace Snex\Render\Engine;

interface EngineInterface
{
    public function render(string $template, array $parameters = []) : string;
}
