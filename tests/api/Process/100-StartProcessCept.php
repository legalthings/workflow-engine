<?php

$I = new ApiTester($scenario);
$I->wantTo('start a process, passing scenario id');

$I->amSignatureAuthenticated("PIw+8VW129YY/6tRfThI3ZA0VygH4cYWxIayUZbdA3I9CKUdmqttvVZvOXN5BX2Z9jfO3f1vD1/R2jxwd3BHBw==");

$I->sendPOST('/processes', [
    'id' => '823d1e54-9009-4745-8901-dd62ec46eaf2',
    'scenario' => '2557288f-108e-4398-8d2d-7914ffd93150',
]);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseContainsJson(['id' => '823d1e54-9009-4745-8901-dd62ec46eaf2']);
$I->seeResponseIsProcess('basic-user-and-system');
