<?php

$I = new ApiTester($scenario);
$I->am('organization');
$I->wantTo('get a prettified scenario by fdefault');

$I->sendGET('/scenarios/2557288f-108e-4398-8d2d-7914ffd93150');

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseIsScenario('basic-user-and-system');
