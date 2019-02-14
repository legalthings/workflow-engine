<?php

//TODO: perform authentication

$I = new ApiTester($scenario);
$I->wantTo('see the error, when trying to perform wrong action');

$response = [
    '$schema' => 'https://specs.livecontracts.io/v1.0.0/response/schema.json#',
    'action' => 'step2',
    'key' => 'ok',
    'actor' => 'system',
    'process' => '4527288f-108e-fk69-8d2d-7914ffd93894'
];

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/responses', $response);

$I->seeResponseCodeIs(400);
$I->seeResponseIsJson();

$I->seeResponseContainsJson(["Action 'step2' isn't allowed in state ':initial'"]);
