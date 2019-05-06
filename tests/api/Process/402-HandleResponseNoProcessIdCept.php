<?php

$I = new ApiTester($scenario);
$I->wantTo('try changing process state, without passing process id');

$I->signRequestAs('organization', 'POST', '/responses');

$response = [
    '$schema' => 'https://specs.livecontracts.io/v0.2.0/response/schema.json#',
    'action' => 'step1',
    'key' => 'ok',
    'actor' => 'system',
    'data' => ['foo' => 'bar']
];

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/processes/-/response', $response);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(400);
$I->seeResponseContainsJson(['Process not specified']);
