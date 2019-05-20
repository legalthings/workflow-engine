<?php

use Jasny\Container\AutowireContainerInterface;
use Jasny\Router\ControllerFactory;

return [
    'controller.factory' => static function(AutowireContainerInterface $container) {
        return new ControllerFactory(function(string $class) use ($container) {
            return $container->autowire($class);
        });
    },
];
