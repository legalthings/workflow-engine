<?php declare(strict_types=1);

use Jasny\Container\AutowireContainerInterface;

return [
    "scenario_events" => static function() {
        return new EventDispatcher();
    },
    ScenarioGateway::class => static function(AutowireContainerInterface $container) {
        return $container->autowire(ScenarioGateway::class);
    },
];
