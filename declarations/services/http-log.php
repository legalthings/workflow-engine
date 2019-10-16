<?php declare(strict_types=1);

use Psr\Container\ContainerInterface;

return [
    HttpRequestLogMiddleware::class => static function (ContainerInterface $container) {
        $logger = $container->get(HttpRequestLogger::class);
        return new HttpRequestLogMiddleware($logger);
    },
    HttpRequestLogger::class => static function (ContainerInterface $container) {
        $db = $container->get('db.default');
        return new HttpRequestLogger($db);
    }
];
