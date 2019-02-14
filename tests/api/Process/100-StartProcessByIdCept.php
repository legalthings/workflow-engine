<?php

//TODO: perform authentication

$I = new ApiTester($scenario);
$I->wantTo('start a process, passing scenario id');

$I->sendPOST('/processes', ['scenario' => '2557288f-108e-4398-8d2d-7914ffd93150']);

$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

$I->seeResponseJsonMatchesJsonPath('$.id');
$I->seeResponseContainsJson(['scenario' => '2557288f-108e-4398-8d2d-7914ffd93150']);
