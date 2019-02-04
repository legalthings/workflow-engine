<?php declare(strict_types=1);

use Jasny\Container\AutowireContainerInterface;
use Jasny\EventDispatcher\EventDispatcher;

return [
    "process_events" => function() {
        return new EventDispatcher();
    },
    ProcessGateway::class => function(AutowireContainerInterface $container) {
        return $container->autowire(ProcessGateway::class);
    },
    ProcessStepper::class => function(AutowireContainerInterface $container) {
        return $container->autowire(ProcessStepper::class);
    },
    TriggerManager::class => function(AutowireContainerInterface $container) {
        return $container->autowire(TriggerManager::class);
    },
    ProcessSimulator::class => function(AutowireContainerInterface $container) {
        return $container->autowire(ProcessSimulator::class);
    },
    ProcessUpdater::class => function(AutowireContainerInterface $container) {
        return $container->autowire(ProcessUpdater::class);
    },
    ProcessInstantiator::class => function(AutowireContainerInterface $container) {
        return $container->autowire(ProcessInstantiator::class);
    },
    StateInstantiator::class => function(AutowireContainerInterface $container) {
        return $container->autowire(StateInstantiator::class);
    },
];
