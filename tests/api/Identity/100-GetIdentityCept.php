<?php

$I = new ApiTester($scenario);
$I->wantTo('get an identity');

$I->sendGET('/identities/1237288f-8u6f-3edt-8d2d-4f4ffd938vk');

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseIsIdentity('developer');
