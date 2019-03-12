<?php

$I = new ApiTester($scenario);
$I->wantTo('delete an identity');

$id = '1237288f-8u6f-3edt-8d2d-4f4ffd938vk';
$I->seeInCollection('identities', ['_id' => $id]);

$I->sendDELETE('/identities/' . $id);
$I->seeResponseCodeIs(200);

$I->dontSeeInCollection('identities', ['_id' => $id]);
