<?php

$I = new ApiTester($scenario);
$I->wantTo('add an identity');

$data = [
    'id' => '9be1f3ed-94fd-4f6b-ab54-962a7bf7dad3',
    'node' => 'amqps://localhost',
    'signkeys' => [
        'user' => '5LucyTBFqSeg8qg4e33uuLY93RZqSQZjmrtsUydUNYgg',
        'system' => 'FkU1XyfrCftc4pQKXCrrDyRLSnifX1SMvmx1CYiiyB3Y',
    ],
    'encryptkey' => 'CLpT61PqmYNpPH5CpJQnYKLpq4kaegjPSG4vY9rGtfm3',
];


$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/identities', $data);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);
$I->seeResponseContainsJson($data);

$I->expectTo('see that the new entity has been persisted');

$I->sendGET('/identities/' . $id);
$I->seeResponseCodeIs(200);
$I->seeResponseContainsJson($expected);
