<?php declare(strict_types=1);

use JmesPath\Env as JmesPath;
use Interop\Container\ContainerInterface;

return [
    'jmespath' => function() {
        return JmesPath::createRuntime();
    },
    DataPatcher::class => function(ContainerInterface $container) {
        return new DataPatcher($container->get('jmespath'));
    },
];
