<?php declare(strict_types=1);

/**
 * Initialize how global errors are being handled.
 * @todo Clean up and simplify.
 */

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Jasny\ErrorHandler;
use Jasny\ErrorHandlerInterface;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use function Jasny\expect_type;

return static function(ContainerInterface $container) {
    /* @var stdClass $logger */
    $config = $container->get('config');

    /* @var Logger $logger */
    $logger = $container->get(LoggerInterface::class);
    expect_type($logger, Logger::class);

    if ((bool)($config->debug ?? false)) {
        error_reporting(E_ALL & ~E_STRICT & ~E_USER_DEPRECATED);

        $display_errors = $config->display_errors ?? $_SERVER['HTTP_X_DISPLAY_ERRORS'] ?? null;

        if ($display_errors !== null) {
            ini_set('display_errors', $display_errors ? '1' : '0');
        }
    } else {
        ini_set('display_error', 'off');
        error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_STRICT);
    }

    $stdlog = array_reduce($logger->getHandlers(), static function($found, $handler) {
        return $found || $handler instanceof ErrorLogHandler;
    }, false);

    if (!(bool)ini_get('display_errors') && !$stdlog) {
        $errorHandler = $container->get(ErrorHandlerInterface::class);

        $errorHandler->setLogger($logger);

        if ($errorHandler instanceof ErrorHandler) {
            $errorHandler->converErrorsToExceptions();
            $errorHandler->logUncaught(E_ALL);
        }
    }
};
