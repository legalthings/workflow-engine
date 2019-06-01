<?php declare(strict_types=1);

use Improved\IteratorPipeline\Pipeline;
use Psr\Container\ContainerInterface;
use Jasny\Container\AutowireContainerInterface;

return [
    TriggerManager::class => static function(AutowireContainerInterface $container) {
        $manager = $container->autowire(TriggerManager::class);
        $configuration = (array)$container->get('config.triggers');

        return Pipeline::with($configuration)
            ->map(static function($settings) use ($container) {
                return (object)[
                    'schema' => $settings->schema ?? null,
                    'trigger' => $container->get($settings->type . '_trigger')
                        ->withConfig($settings, $container),
                ];
            })
            ->reduce(static function(TriggerManager $manager, stdClass $entry): TriggerManager {
                return $manager->with($entry->schema, $entry->trigger);
            }, $manager);
    },
    HookManager::class => static function(AutowireContainerInterface $container) {
        $manager = $container->autowire(HookManager::class);
        $configuration = (array)$container->get('config.hooks');

        return Pipeline::with($configuration)
            ->map(static function($settings) use ($container) {
                return (object)[
                    'schema' => $settings->schema ?? null,
                    'trigger' => $container->get($settings->type . '_trigger')
                        ->withConfig($settings, $container),
                ];
            })
            ->reduce(static function(HookManager $manager, stdClass $entry): HookManager {
                return $manager->with($entry->schema, $entry->trigger);
            }, $manager);
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
