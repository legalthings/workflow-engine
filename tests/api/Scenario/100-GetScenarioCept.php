<?php

$I = new ApiTester($scenario);
$I->wantTo('get a scenario');

$I->sendGET('/scenarios/2557288f-108e-4398-8d2d-7914ffd93150', ['view' => 'pretty']);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

// TODO Move this to API helper
$expectedJson = file_get_contents(__DIR__ . '/../../_data/scenarios/basic-user-and-system.json');
$expected = json_decode($expectedJson, true);

$I->canSeeResponseContainsJson($expected);

// Replace above with
//    $I->canSeeResponseIsScenario('basic-user-and-system');
// ----
