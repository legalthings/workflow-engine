<?php

$I = new ApiTester($scenario);
$I->wantTo('add an identity');

$json = file_get_contents(__DIR__ . '/../../_data/identities/developer-identity.json');
$data = json_decode($json, true);

unset($data['id']);

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST('/identities', $data);

$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

$I->canSeeResponseContainsJson($data);
$I->seeResponseJsonMatchesJsonPath('$.id');
