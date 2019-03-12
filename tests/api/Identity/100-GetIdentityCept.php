<?php

$I = new ApiTester($scenario);
$I->wantTo('get an identity');

$I->sendGET('/identities/1237288f-8u6f-3edt-8d2d-4f4ffd938vk');

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseContainsJson([
    'id' => '1237288f-8u6f-3edt-8d2d-4f4ffd938vk',
    'node' => 'amqps://localhost',
    'signkeys' => [
        'user' => '57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn',
        'system' => 'FkU1XyfrCftc4pQKXCrrDyRLSnifX1SMvmx1CYiiyB3Y',
    ],
    'encryptkey' => '9fSos8krst114LtaYGHQPjC3h1CQEHUQWEkYdbykrhHv',
]);
