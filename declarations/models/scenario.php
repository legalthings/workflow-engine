<?php declare(strict_types=1);

use Jasny\Container\AutowireContainerInterface;

return [
    ScenarioGateway::class => function(AutowireContainerInterface $container) {
        return $container->autowire(ScenarioGateway::class);
    },
];
