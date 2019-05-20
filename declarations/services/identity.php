<?php declare(strict_types=1);

use Jasny\Container\AutowireContainerInterface;

return [
    IdentityGateway::class => static function(AutowireContainerInterface $container) {
        return $container->autowire(IdentityGateway::class);
    },
];
