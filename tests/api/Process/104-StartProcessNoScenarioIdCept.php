<?php

$I = new ApiTester($scenario);
$I->wantTo('try starting a process, without passing scenario id');

$I->signRequestAs('organization', 'POST', '/processes');

$I->sendPOST('/processes', [
    'id' => '823d1e54-9009-4745-8901-dd62ec46eaf2',
    'actors' => [
        'organization' => '6uk7288s-afe4-7398-8dbh-7914ffd930pl',
    ],
]);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(400);
$I->seeResponseContainsJson(['Scenario not specified']);
