<?php declare(strict_types=1);

use Psr\Container\ContainerInterface;

return [
    'identity' => static function(RouterInterface $router, ContainerInterface $container) {
        if (!$container->has('config.lto.account')) {
            return null;
        }

        $node = $container->get(LTO\Account::class);
        $disableAuth = $container->has('config.noauth') ? $container->get('config.noauth') : false;

        return new IdentityMiddleware($node, $disableAuth);
    },
];
