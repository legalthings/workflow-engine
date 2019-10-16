<?php

$I = new ApiTester($scenario);
$I->am('organization');
$I->wantTo('get a scenario not prettyfied');

$I->haveHttpHeader('Accept', 'application/json;view=complete');
$I->sendGET('/scenarios/2557288f-108e-4398-8d2d-7914ffd93150');

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseContainsJson([
    'title' => 'Basic system and user',
    'description' => null
]);
