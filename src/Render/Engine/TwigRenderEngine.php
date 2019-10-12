<?php

namespace Snex\Render\Engine;

use Snex\Service\ServiceInterface;
use Twig\Environment;
use Snex\Render\Twig\TwigTemplateLoader;

class TwigRenderEngine implements EngineInterface, ServiceInterface
{
    protected $twigLoader;
    protected $twigEnvironment;

    public function __construct()
    {
        $this->twigLoader = new TwigTemplateLoader();
        $this->twigEnvironment = new Environment($this->twigLoader);
    }

    public function render(string $template, array $parameters = []) : string
    {
        return $this->twigEnvironment->render($template, $parameters);
    }
}
