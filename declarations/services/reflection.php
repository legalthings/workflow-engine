<?php

use Jasny\ReflectionFactory\ReflectionFactory;

return [
    ReflectionFactory::class => static function() {
        return new ReflectionFactory();
    }
];
