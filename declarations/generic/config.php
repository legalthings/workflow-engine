<?php

use Psr\Container\ContainerInterface;

return [
    'config' => static function(ContainerInterface $container) {
        return (new AppConfig)->load((string)$container->get('app.env'));
    }
];
