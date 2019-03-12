<?php declare(strict_types=1);

use Jasny\RouterInterface;
use Jasny\HttpDigest\HttpDigest;
use Jasny\HttpDigest\ServerMiddleware as HttpDigestMiddleware;
use Jasny\HttpDummy\ServerMiddleware as HttpDummyMiddleware;
use Jasny\HttpSignature\HttpSignature;
use Jasny\HttpSignature\ServerMiddleware as HttpSignatureMiddleware;
use Psr\Container\ContainerInterface;
use LTO\Account\ServerMiddleware as AccountMiddleware;
use LTO\AccountFactory;

return [
    static function (RouterInterface $router, ContainerInterface $container) {
        // Disable digest verification when in debug mode.
        if ((bool)$container->get('config.debug')) {
            return (new HttpDummyMiddleware)->asDoublePass();
        }

        $service = $container->get(HttpDigest::class);
        $middleware = new HttpDigestMiddleware($service);

        return $middleware->asDoublePass();
    },
    static function (RouterInterface $router, ContainerInterface $container) {
        $service = $container->get(HttpSignature::class);
        $middleware = new HttpSignatureMiddleware($service);

        return $middleware->asDoublePass();
    },
    static function (RouterInterface $router, ContainerInterface $container) {
        $accountFactory = $container->get(AccountFactory::class);
        $middleware = new AccountMiddleware($accountFactory);

        return $middleware->asDoublePass();
    },
];
