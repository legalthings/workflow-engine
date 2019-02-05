<?php declare(strict_types=1);

return [
    TriggerManager::class => function(AutowireContainerInterface $container) {
        $manager = $container->autowire(TriggerManager::class)
            ->with()
            ->with()
            ->with();
    },
];
