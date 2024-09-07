<?php

namespace Az\Route;

trait RouteController
{
    public function controller($pattern, $controller, $name = null)
    {
        if ($name === true) {
            $name = strtolower((new \ReflectionClass($controller))->getShortName());
        }

        return $this->add($pattern, $controller, $name)
            ->tokens(['action' => '[\w]*',])
            ->filter(function ($route) {
                $handler = $route->getHandler();
                return is_callable([container()->get($handler[0]), $handler[1]]);
            });
    }
}
