<?php

$I = new ApiTester($scenario);
$I->wantTo('update an identity');

$info = [
    "name" => "Lance",
    "city" => "Dublin"
];
$node = "amqps://localhost-new";

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/identities/1237288f-8u6f-3edt-8d2d-4f4ffd938vk', [
    "info" => $info,
    "node" => $node
]);

$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

// TODO Move this to API helper
$expectedJson = file_get_contents(__DIR__ . '/../../_data/identities/developer-identity.json');
$expected = json_decode($expectedJson, true);

$expected['info'] = $info;
$expected['node'] = $node;

$I->canSeeResponseContainsJson($expected);
