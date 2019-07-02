<?php declare(strict_types=1);

use Psr\Container\ContainerInterface;

return [
    HttpLogMiddleware::class => static function(ContainerInterface $container) {
        return new HttpLogMiddleware();
    }
];
