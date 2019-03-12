<?php

$I = new ApiTester($scenario);
$I->wantTo('get a scenario');

$I->sendGET('/scenarios/2557288f-108e-4398-8d2d-7914ffd93150', ['view' => 'pretty']);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseIsScenario('basic-user-and-system');
