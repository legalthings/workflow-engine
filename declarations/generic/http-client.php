<?php

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;

return [
    ClientInterface::class => static function() {
        return new Client(['timeout' => 20]);
    },
];
