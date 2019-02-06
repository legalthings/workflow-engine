<?php

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Jasny\Router\RoutesInterface;
use Jasny\RouterInterface;
use Jasny\Router;
use Jasny\Router\Routes;
use Jasny\Router\Runner\Controller as Runner;
use Symfony\Component\Yaml\Yaml;

return [
    'dummy-middleware' => static function() {
        return static function(ServerRequest $request, Response $response, callable $next) {
            return $next($request, $response);
        };
    },
    'router.routes' => static function() {
        return Yaml::parse(file_get_contents('config/routes.yml'));
    },
    'router.middleware' => static function() {
        $sources = glob('declarations/middleware/*.php');

        return array_reduce($sources, static function(array $middleware, string $source) {
            $declaration = include $source;
            return $middleware + $declaration;
        }, []);
    },
    'router.runner' => static function(ContainerInterface $container) {
        return (new Runner())->withFactory($container->get('controller.factory'));
    },

    RoutesInterface::class => static function(ContainerInterface $container) {
        return new Routes\Glob($container->get('router.routes'));
    },
    RouterInterface::class => static function(ContainerInterface $container) {
        $router = new Router($container->get(RoutesInterface::class));
        $router->setRunner($container->get('router.runner'));

        $middleware = $container->get('router.middleware');

        foreach ($middleware as $fn) {
            $router->add($fn($router, $container));
        }

        return $router;
    },

    // Alias
    'router' => static function(ContainerInterface $container) {
        return $container->get(RouterInterface::class);
    }
];
