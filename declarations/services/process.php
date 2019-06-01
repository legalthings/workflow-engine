<?php declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Jasny\Container\AutowireContainerInterface;
use Jasny\EventDispatcher\EventDispatcher;

return [
    "process_events" => static function(ContainerInterface $container) {
        $identityGateway = $container->get(IdentityGateway::class);
        $expandIdentities = new ExpandIdentities($identityGateway);

        $hookManager = $container->get(HookManager::class);

        return (new EventDispatcher)
            ->on('fetch', $expandIdentities)
            ->on('instantiate', $expandIdentities)
            ->on('update', $hookManager);
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
