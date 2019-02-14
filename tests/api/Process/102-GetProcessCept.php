<?php

//TODO: perform authentication

$I = new ApiTester($scenario);
$I->wantTo('get a process');

$I->sendGET('/processes/4527288f-108e-fk69-8d2d-7914ffd93894');

$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

// TODO Move this to API helper
$expectedJson = file_get_contents(__DIR__ . '/../../_data/processes/basic-user-and-system.json');
$expected = json_decode($expectedJson, true);

$I->seeResponseContainsJson($expected);

