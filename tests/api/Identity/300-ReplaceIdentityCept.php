<?php

$I = new ApiTester($scenario);
$I->am('node');
$I->wantTo('replace the organization identity');

$id = '1237288f-8u6f-3edt-8d2d-4f4ffd938vk';

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/identities', [
    'id' => $id,
    'node' => 'amqps://example.com',
    'signkeys' => [
        'default' => '57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn',
        'system' => '2gYvvF9nyjaC5Qv3mUDFkbqXNEWDtoJxZKwnHEtGRDzP',
    ],
]);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$expected = [
    'id' => '1237288f-8u6f-3edt-8d2d-4f4ffd938vk',
    'node' => 'amqps://example.com',
    'signkeys' => [
        'default' => '57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn',
        'system' => '2gYvvF9nyjaC5Qv3mUDFkbqXNEWDtoJxZKwnHEtGRDzP',
    ],
    'encryptkey' => null,
    'authz' => 'user'
];

$I->seeResponseContainsJson($expected);

$I->expectTo('see that changes have been persisted');

$I->sendGET('/identities/' . $id);
$I->seeResponseCodeIs(200);
$I->seeResponseContainsJson($expected);
