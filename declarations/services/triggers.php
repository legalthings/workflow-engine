<?php declare(strict_types=1);

use Improved\IteratorPipeline\Pipeline;
use Psr\Container\ContainerInterface;
use Jasny\Container\AutowireContainerInterface;

$configuredManager = static function($manager, $configuration, $container) {
    return Pipeline::with($configuration)
        ->map(static function($settings) use ($container) {
            return (object)[
                'schema' => $settings->schema ?? null,
                'trigger' => $container->get($settings->type . '_trigger')
                    ->withConfig($settings, $container),
            ];
        })
        ->reduce(static function($manager, stdClass $entry) {
            return $manager->with($entry->schema, $entry->trigger);
        }, $manager);
};

return [
    TriggerManager::class => static function(AutowireContainerInterface $container) use ($configuredManager) {
        $manager = $container->autowire(TriggerManager::class);
        $configuration = $container->get('config.triggers');

        return $configuredManager($manager, $configuration, $container);
    },
    HookManager::class => static function(AutowireContainerInterface $container) use ($configuredManager) {
        $manager = $container->autowire(HookManager::class);
        $configuration = $container->get('config.hooks');

        return $configuredManager($manager, $configuration, $container);
    },

    'nop_trigger' => static function(AutowireContainerInterface $container) {
        return $container->autowire(Trigger\Nop::class);
    },
    'http_trigger' => static function(AutowireContainerInterface $container) {
        return $container->autowire(Trigger\Http::class);
    },
    'event_trigger' => static function(AutowireContainerInterface $container) {
        return $container->autowire(Trigger\Event::class, $container->get('event.create'));
    },
    'sequence_trigger' => static function() {
        return new Trigger\Sequence();
    },
];
