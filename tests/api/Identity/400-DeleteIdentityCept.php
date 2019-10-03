<?php

$I = new ApiTester($scenario);
$I->am('node');
$I->wantTo('delete the organization identity');

$id = '1237288f-8u6f-3edt-8d2d-4f4ffd938vk';

$I->sendDELETE('/identities/' . $id);
$I->seeResponseCodeIs(200);

$I->expectTo('see that changes have been persisted');

$I->sendGET('/identities/' . $id);
$I->seeResponseCodeIs(404);
