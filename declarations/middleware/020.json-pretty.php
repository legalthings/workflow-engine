<?php declare(strict_types=1);

use Jasny\RouterInterface;
use Psr\Container\ContainerInterface;

return [
    static function(RouterInterface $router, ContainerInterface $container) {
        return new PrettyJsonMiddleware();
    },
];
