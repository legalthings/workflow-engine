<?php

$I = new ApiTester($scenario);
$I->wantTo('start a process, passing scenario id');

$I->amSignatureAuthenticated("PIw+8VW129YY/6tRfThI3ZA0VygH4cYWxIayUZbdA3I9CKUdmqttvVZvOXN5BX2Z9jfO3f1vD1/R2jxwd3BHBw==");

$I->sendPOST('/processes', ['scenario' => '2557288f-108e-4398-8d2d-7914ffd93150']);

$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

$I->seeResponseJsonMatchesJsonPath('$.id');
$I->seeResponseContainsJson(['scenario' => '2557288f-108e-4398-8d2d-7914ffd93150']);
