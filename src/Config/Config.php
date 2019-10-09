<?php

namespace Snex\Config;

use Symfony\Component\HttpFoundation\ParameterBag;

class Config extends ParameterBag
{
    public function merge(Config $config) : void
    {
        foreach ($config as $name => $value) {
            $currentValue = $this->get($name);

            if (isset($currentValue) && is_array($value)) {
                $this->set($name, $this->mergeRecursively($currentValue, $value));
            } else {
                $this->set($name, $value);
            }
        }
    }

    protected function mergeRecursively(array $currentValue, array $newValue) : array
    {
        $shouldMerge = true;

        foreach ($newValue as $name => $value) {
            if (!is_integer($name)) {
                $shouldMerge = false;
                break;
            }
        }

        if ($shouldMerge) {
            foreach ($newValue as $name => $value) {
                $newValue[$name] = $value;
            }

            $currentValue = array_merge($currentValue, $newValue);
        } else {
            foreach ($newValue as $name => $value) {
                if (is_array($value) && isset($currentValue[$name])) {
                    $currentValue[$name] = $this->mergeRecursively($currentValue[$name], $value);
                } else {
                    $currentValue[$name] = $value;
                }
            }
        }

        return $currentValue;
    }
}
