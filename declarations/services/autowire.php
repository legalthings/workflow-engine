<?php
declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Jasny\Autowire\AutowireInterface;
use Jasny\Autowire\ReflectionAutowire;
use Jasny\ReflectionFactory\ReflectionFactory;

return [
    AutowireInterface::class => static function(ContainerInterface $container) {
        $reflection = $container->get(ReflectionFactory::class);

        return new ReflectionAutowire($container, $reflection);
    }
];
