<?php declare(strict_types=1);

use Improved\IteratorPipeline\Pipeline;
use Psr\Container\ContainerInterface;
use Jasny\Container\AutowireContainerInterface;

return [
    TriggerManager::class => static function(AutowireContainerInterface $container) {
        $manager = $container->autowire(TriggerManager::class);

        return Pipeline::with((array)$container->get('config.triggers'))
            ->map(static function($settings, $key) use ($container) {
                return (object)[
                    'schema' => $settings->schema ?? null,
                    'trigger' => $container->get(($settings->type ?? $key) . '_trigger')
                        ->withConfig($settings, $container),
                ];
            })
            ->reduce(static function(TriggerManager $manager, stdClass $entry): TriggerManager {
                return $manager->with($entry->schema, $entry->trigger);
            }, $manager);
    },

    'nop_trigger' => static function(ContainerInterface $container) {
        return new Trigger\Nop($container->get('jmespath'));
    },
    'http_trigger' => static function(ContainerInterface $container) {
        return new Trigger\Http($container->get('jmespath'));
    },
    'event_trigger' => static function(ContainerInterface $container) {
        return new Trigger\Event($container->get('jmespath'));
    },
    'sequence_trigger' => static function() {
        return new Trigger\Sequence();
    },
];
