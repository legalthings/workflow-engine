<?php

/**
 * Application environment.
 * Other environment variables are loaded via the config.
 */

use Jasny\ApplicationEnv;
use Psr\Container\ContainerInterface;

return [
    ApplicationEnv::class => static function() {
        $env = getenv('APPLICATION_ENV') ?: 'dev';

        if ($env === 'dev' && isset($_SERVER['HTTP_X_APPLICATION_ENV'])) {
            $env = $_SERVER['HTTP_X_APPLICATION_ENV'];
        }

        if (isset($_SERVER['HTTP_HOST'])) {
            $env .= '.' . preg_replace('/^www\.[\w\-]+\./', '', $_SERVER['HTTP_HOST']);
        }

        return new ApplicationEnv($env);
    },
    'app.env' => static function(ContainerInterface $container) {
        return $container->get(ApplicationEnv::class);
    }
];
