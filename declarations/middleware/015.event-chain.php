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
            if ($request->hasHeader('X-Event-Chain') && $container->has(EventChainRepository::class)) {
                /** @var EventChainRepository $repository */
                $repository = $container->get(EventChainRepository::class);

                foreach ($request->getHeader('X-Event-Chain') as $header) {
                    [$id, $latestHash] = explode(':', $header, 2);
                    $eventChain = new EventChain($id, $latestHash);

                    $repository->register($eventChain);
                }
            }

            return $next($request, $response);
        };
    },
];
