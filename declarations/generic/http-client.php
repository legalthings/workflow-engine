<?php declare(strict_types=1);

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;

return [
    ClientInterface::class => static function() {
        return new Client(['timeout' => 20]);
    },
    Client::class => static function() {
        return new Client(['timeout' => 20]);
    },
];
