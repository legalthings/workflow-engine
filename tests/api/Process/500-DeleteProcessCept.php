<?php

$I = new ApiTester($scenario);
$I->wantTo('delete process');

$I->am('system');

$id = '4527288f-108e-fk69-8d2d-7914ffd93894';
$I->seeInCollection('processes', ['_id' => $id]);

$I->sendDELETE('/processes/' . $id);
$I->seeResponseCodeIs(200);

$I->dontSeeInCollection('processes', ['_id' => $id]);
