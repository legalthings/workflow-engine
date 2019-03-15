<?php

$I = new ApiTester($scenario);
$I->wantTo('get a process');

$I->am('organization');

$I->sendGET('/processes/4527288f-108e-fk69-8d2d-7914ffd93894');

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseIsProcess('basic-user-and-system');
