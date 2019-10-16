<?php declare(strict_types=1);

use Psr\Container\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use Jasny\HttpDigest\ClientMiddleware as HttpDigestMiddleware;
use Jasny\HttpSignature\ClientMiddleware as HttpSignatureMiddleware;

return [
    HandlerStack::class => static function(ContainerInterface $container) {
        $config = $container->get('config.http_request_log');

        $stack = HandlerStack::create();
        $stack->setHandler(new CurlHandler());

        $stack->push($container->get(HttpDigestMiddleware::class)->forGuzzle());
        $stack->push($container->get(HttpSignatureMiddleware::class)->forGuzzle());

        if ($config->http_request_log ?? false) {
            $logMiddleware = $container->get(HttpRequestLogMiddleware::class);
            $stack->push($logMiddleware);            
        }

        return $stack;
    },
    ClientInterface::class => static function (ContainerInterface $container) {
        $stack = $container->get(HandlerStack::class);

        return new Client(['handler' => $stack, 'timeout' => 20]);
    },
    Client::class => static function (ContainerInterface $container) {
        return $container->get(ClientInterface::class); // Alias
    }
];
