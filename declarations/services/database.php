<?php

declare(strict_types=1);

use Jasny\DB;
use Jasny\DB\Connection;

// Getting database from global scope for now :-(

return [
    'db.default' => static function() {
        return DB::conn('default');
    },
    Connection::class => static function() {
        return DB::conn('default');
    },
];
