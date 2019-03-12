<?php

$I = new ApiTester($scenario);
$I->wantTo('change process state');

$I->amSignatureAuthenticated("PIw+8VW129YY/6tRfThI3ZA0VygH4cYWxIayUZbdA3I9CKUdmqttvVZvOXN5BX2Z9jfO3f1vD1/R2jxwd3BHBw==");

$response = [
    '$schema' => 'https://specs.livecontracts.io/v0.2.0/response/schema.json#',
    'action' => 'step1',
    'key' => 'ok',
    'actor' => 'system',
    'process' => '4527288f-108e-fk69-8d2d-7914ffd93894',
    'data' => ['foo' => 'bar']
];

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/responses', $response);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseIsProcess('basic-user-and-system', 'second-state');
