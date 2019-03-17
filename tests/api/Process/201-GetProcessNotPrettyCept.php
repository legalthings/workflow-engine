<?php

$I = new ApiTester($scenario);
$I->wantTo('get a process not prettyfied');

$I->signRequestAs('organization', 'POST', '/processes');

$I->haveHttpHeader('Accept', 'application/json;view=complete');
$I->sendGET('/processes/4527288f-108e-fk69-8d2d-7914ffd93894');

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseContainsJson([
    'scenario' => [
        'id' => '2557288f-108e-4398-8d2d-7914ffd93150'
    ]
]);
