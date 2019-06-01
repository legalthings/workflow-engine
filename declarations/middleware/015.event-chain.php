<?php declare(strict_types=1);

/**
 * Middleware for the X-Event-Chain header.
 * Add a partial event chain (without events) to the event-chain repository.
 */

use Jasny\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use LTO\EventChain;

return [
    static function(RouterInterface $router, ContainerInterface $container) {
        return function(Request $request, Response $response, callable $next) use ($container): Response {
            if (!$request->hasHeader('X-Event-Chain') || !$container->has(EventChainRepository::class)) {
                return $next($request, $response);
            }

            /** @var EventChainRepository $repository */
            $repository = $container->get(EventChainRepository::class);

            [$id, $latestHash] = explode(':', $request->getHeaderLine('X-Event-Chain'), 2);
            $eventChain = new EventChain($id, $latestHash);

            $repository->register($eventChain);
            $nextRequest = $request->withAttribute('event-chain', $eventChain);

            return $next($nextRequest, $response);
        };
    },
];
