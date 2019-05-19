<?php

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Monolog\Handler\PsrHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;

return [
    LoggerInterface::class => static function(ContainerInterface $container) {
        $handler = new ErrorLogHandler();
        return new Logger('', [$handler]);
    },

    // Alias
    'logger' => static function(ContainerInterface $container) {
        return $container->get(LoggerInterface::class);
    }
];
