<?php declare(strict_types=1);

use Jasny\RouterInterface;
use Psr\Container\ContainerInterface;

return [
    'session' => function(RouterInterface $router, ContainerInterface $container) {
        if ($container->get(SessionManager::class)->isMocked()) {
            return $container->get('dummy-middleware');
        }

        $alwaysStart = !$container->has('config.iam') || !(bool)$container->get('config.iam');

        return new SessionMiddleware($alwaysStart);
    }
];

