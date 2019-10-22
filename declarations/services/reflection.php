<?php
declare(strict_types=1);

use Jasny\ReflectionFactory\ReflectionFactory;

return [
    ReflectionFactory::class => static function() {
        return new ReflectionFactory();
    }
];
