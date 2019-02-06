<?php declare(strict_types=1);

use Jasny\RouterInterface;
use Psr\Container\ContainerInterface;
use LTO\AccountFactory;

return [
    'http-signature' => static function(RouterInterface $router, ContainerInterface $container) {
        $accountFactory = $container->get(AccountFactory::class);
        $baseRewrite = defined('BASE_REWRITE') ? BASE_REWRITE : null;

        return new HTTPSignatureMiddleware($accountFactory, $baseRewrite);
    }
];
