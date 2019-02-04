<?php declare(strict_types=1);

use Jasny\Container\AutowireContainerInterface;

return [
    "scenario_events" => function() {
        return new EventDispatcher();
    },
    ScenarioGateway::class => function(AutowireContainerInterface $container) {
        return $container->autowire(ScenarioGateway::class);
    },
];
