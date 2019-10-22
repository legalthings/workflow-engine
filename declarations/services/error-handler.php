<?php
declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Jasny\ErrorHandlerInterface;
use Jasny\ErrorHandler;
use Psr\Log\LoggerInterface;

return [
    ErrorHandlerInterface::class => static function(ContainerInterface $container) {
        $errorHandler = new ErrorHandler();

        $logger = $container->get(LoggerInterface::class);
        if (isset($logger)) {
            $errorHandler->setLogger($logger);
        }

        return $errorHandler;
    },

    // Alias
    'errorHandler' => static function(ContainerInterface $container) {
        return $container->get(ErrorHandlerInterface::class);
    }
];
