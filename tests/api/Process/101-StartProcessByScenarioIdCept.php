<?php

$I = new ApiTester($scenario);
$I->wantTo('start a process, passing scenario id');

$I->am('organization');

$I->sendPOST('/processes', ['scenario' => '2557288f-108e-4398-8d2d-7914ffd93150']);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseJsonMatchesJsonPath('$.id');
$I->seeResponseContainsJson(['scenario' => '2557288f-108e-4398-8d2d-7914ffd93150']);
