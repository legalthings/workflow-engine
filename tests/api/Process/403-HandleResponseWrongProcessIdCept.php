<?php

$I = new ApiTester($scenario);
$I->wantTo('try changing process state, if process id in data does not match id in url');

$I->signRequestAs('organization', 'POST', '/responses');

$response = [
    '$schema' => 'https://specs.livecontracts.io/v0.2.0/response/schema.json#',
    'action' => 'step1',
    'key' => 'ok',
    'actor' => 'system',
    'process' => '4527288f-108e-fk69-8d2d-7914ffd93894',
    'data' => ['foo' => 'bar']
];

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/processes/7527288f-108e-fk69-8d2d-7914ffd93894/response', $response);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(400);
$I->seeResponseContainsJson(['Incorrect process id']);
