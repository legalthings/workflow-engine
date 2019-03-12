<?php

$I = new ApiTester($scenario);
$I->wantTo('update an identity');

$id = '1237288f-8u6f-3edt-8d2d-4f4ffd938vk';

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/identities/' . $id, [
    'node' => 'amqps://example.com',
    'signkeys' => [
        'user' => '57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn',
        'system' => '2gYvvF9nyjaC5Qv3mUDFkbqXNEWDtoJxZKwnHEtGRDzP',
    ]
]);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$expected = [
    'id' => '1237288f-8u6f-3edt-8d2d-4f4ffd938vk',
    'node' => 'amqps://example.com',
    'signkeys' => [
        'user' => '57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn',
        'system' => '2gYvvF9nyjaC5Qv3mUDFkbqXNEWDtoJxZKwnHEtGRDzP',
    ],
    'encryptkey' => '9fSos8krst114LtaYGHQPjC3h1CQEHUQWEkYdbykrhHv',
];

$I->seeResponseContainsJson($expected);

$I->expectTo('see that changes have been persisted');

$I->sendGET('/identities/' . $id);
$I->seeResponseCodeIs(200);
$I->seeResponseContainsJson($expected);
