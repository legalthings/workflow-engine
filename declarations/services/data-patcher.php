<?php declare(strict_types=1);

use JmesPath\Env as JmesPath;
use Interop\Container\ContainerInterface;

return [
    'jmespath' => static function() {
        return JmesPath::createRuntime();
    },
    DataPatcher::class => static function(ContainerInterface $container) {
        return new DataPatcher($container->get('jmespath'));
    },
];
