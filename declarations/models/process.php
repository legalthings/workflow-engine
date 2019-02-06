<?php declare(strict_types=1);

use Jasny\Container\AutowireContainerInterface;
use Jasny\EventDispatcher\EventDispatcher;

return [
    "process_events" => static function() {
        return new EventDispatcher();
    },
    ProcessGateway::class => static function(AutowireContainerInterface $container) {
        return $container->autowire(ProcessGateway::class);
    },
    ProcessStepper::class => static function(AutowireContainerInterface $container) {
        return $container->autowire(ProcessStepper::class);
    },
    ProcessSimulator::class => static function(AutowireContainerInterface $container) {
        return $container->autowire(ProcessSimulator::class);
    },
    ProcessUpdater::class => static function(AutowireContainerInterface $container) {
        return $container->autowire(ProcessUpdater::class);
    },
    ProcessInstantiator::class => static function(AutowireContainerInterface $container) {
        return $container->autowire(ProcessInstantiator::class);
    },
    StateInstantiator::class => static function(AutowireContainerInterface $container) {
        return $container->autowire(StateInstantiator::class);
    },
];
