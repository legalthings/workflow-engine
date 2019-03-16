<?php

$I = new ApiTester($scenario);
$I->wantTo('delete process');

$I->signRequestAs('organization', 'DELETE', '/processes');

$id = '4527288f-108e-fk69-8d2d-7914ffd93894';

$I->sendGET('/processes/' . $id);
$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);
$I->seeResponseContainsJson(['id' => $id]);

$I->sendDELETE('/processes/' . $id);
$I->seeResponseCodeIs(200);

$I->sendGET('/processes/' . $id);
$I->seeResponseCodeIs(404);
$I->seeResponseContains('Process not found');
