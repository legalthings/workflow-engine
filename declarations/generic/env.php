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

        return new ApplicationEnv($env);
    },
    'app.env' => static function(ContainerInterface $container) {
        return $container->get(ApplicationEnv::class);
    }
];
