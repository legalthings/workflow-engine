<?php

$I = new ApiTester($scenario);
$I->wantTo('get a prettified scenario');

$I->haveHttpHeader('Accept', 'application/json;view=pretty');
$I->sendGET('/scenarios/2557288f-108e-4398-8d2d-7914ffd93150');

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseIsScenario('basic-user-and-system');
