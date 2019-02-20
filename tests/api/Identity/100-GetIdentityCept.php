<?php

$I = new ApiTester($scenario);
$I->wantTo('get an identity');

$I->sendGET('/identities/1237288f-8u6f-3edt-8d2d-4f4ffd938vk');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

// TODO Move this to API helper
$expectedJson = file_get_contents(__DIR__ . '/../../_data/identities/developer-identity.json');
$expected = json_decode($expectedJson, true);

$I->canSeeResponseContainsJson($expected);

// Replace above with
//    $I->canSeeResponseIsScenario('basic-user-and-system');
// ----
