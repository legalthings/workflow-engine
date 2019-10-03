<?php

$I = new ApiTester($scenario);
$I->am('organization');
$I->wantTo('get a scenario with update instructions');

$I->sendGET('/scenarios/rt5yh683-108e-5673-8d2d-7914ffd23e5t', ['view' => 'pretty']);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseIsScenario('basic-user-and-system.update-instructions');
