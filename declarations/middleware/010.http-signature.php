<?php declare(strict_types=1);

use Jasny\RouterInterface;
use Jasny\HttpDigest\HttpDigest;
use Jasny\HttpDigest\ServerMiddleware as HttpDigestMiddleware;
use Jasny\HttpSignature\HttpSignature;
use Jasny\HttpSignature\ServerMiddleware as HttpSignatureMiddleware;
use Jasny\Dummy\ServerMiddleware as DummyMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use LTO\Account;
use LTO\Account\ServerMiddleware as AccountMiddleware;
use LTO\AccountFactory;

return [
    static function (RouterInterface $router, ContainerInterface $container) {
        $service = $container->get(HttpDigest::class);
        $middleware = (new HttpDigestMiddleware($service))
            ->withOptionalDigest($container->get('config.digest') === 'optional');

        return $middleware->asDoublePass();
    },
    static function (RouterInterface $router, ContainerInterface $container) {
        $service = $container->get(HttpSignature::class);
        $middleware = new HttpSignatureMiddleware($service);

        return $middleware->asDoublePass();
    },
    static function (RouterInterface $router, ContainerInterface $container) {
        $account = $container->get(Account::class);
        $accountFactory = $container->get(AccountFactory::class);

        $middleware = (new AccountMiddleware($accountFactory))->withTrustedAccount($account);

        return $middleware->asDoublePass();
    },
    static function (RouterInterface $router, ContainerInterface $container) {
        if (!$container->get('config.noauth')) {
            return (new DummyMiddleware)->asDoublePass(); // On production we always want signed requests.
        }

        $account = $container->get(Account::class);

        return function(ServerRequest $request, Response $response, callable $next) use ($account) {
            $nextRequest = $request->getAttribute('account') === null
                ? $request->withAttribute('account', $account)
                : $request;

            return $next($nextRequest, $response);
        };
    },
];
