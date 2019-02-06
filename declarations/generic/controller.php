<?php

use Psr\Container\ContainerInterface;

return [
    'controller.factory' => static function(ContainerInterface $container) {
        return new ControllerFactory(null, $container);
    }
];
