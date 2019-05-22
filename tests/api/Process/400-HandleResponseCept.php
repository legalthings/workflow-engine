<?php

$I = new ApiTester($scenario);
$I->wantTo('change process state');

$I->signRequestAs('organization', 'POST', '/responses');

$response = [
    '$schema' => 'https://specs.livecontracts.io/v0.2.0/response/schema.json#',
    'action' => 'step1',
    'key' => 'ok',
    'actor' => 'organization',
    'process' => '4527288f-108e-fk69-8d2d-7914ffd93894',
    'data' => ['foo' => 'bar']
];

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/processes/-/response', $response);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);
$I->seeResponseIsProcess('basic-user-and-system', 'second-state');

$I->expectTo('see correctly persisted process');

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendGET('/processes/4527288f-108e-fk69-8d2d-7914ffd93894');

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);
$I->seeResponseIsProcess('basic-user-and-system', 'second-state');
