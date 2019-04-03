<?php

$I = new ApiTester($scenario);
$I->wantTo('start a process, passing scenario id');

$I->signRequestAs('organization', 'POST', '/processes');

$I->sendPOST('/processes', [
    'id' => '823d1e54-9009-4745-8901-dd62ec46eaf2',
    'scenario' => '2557288f-108e-4398-8d2d-7914ffd93150',
    'actors' => [
        'organization' => '6uk7288s-afe4-7398-8dbh-7914ffd930pl',
    ],
]);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseContainsJson(['id' => '823d1e54-9009-4745-8901-dd62ec46eaf2']);
$I->seeResponseIsProcess('basic-user-and-system', 'no-user-identity');
