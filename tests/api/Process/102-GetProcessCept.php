<?php

$I = new ApiTester($scenario);
$I->wantTo('get a process');

$I->amSignatureAuthenticated("PIw+8VW129YY/6tRfThI3ZA0VygH4cYWxIayUZbdA3I9CKUdmqttvVZvOXN5BX2Z9jfO3f1vD1/R2jxwd3BHBw==");

$I->sendGET('/processes/4527288f-108e-fk69-8d2d-7914ffd93894');

$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

// TODO Move this to API helper
$expectedJson = file_get_contents(__DIR__ . '/../../_data/processes/basic-user-and-system.json');
$expected = json_decode($expectedJson, true);

$I->seeResponseContainsJson($expected);

