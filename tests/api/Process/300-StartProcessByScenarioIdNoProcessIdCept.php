<?php

$I = new ApiTester($scenario);
$I->wantTo('get error starting a process, passing scenario id without process id');

$I->signRequestAs('organization', 'POST', '/processes');

$I->sendPOST('/processes', ['scenario' => '2557288f-108e-4398-8d2d-7914ffd93150']);

$I->seeResponseCodeIs(400);
$I->seeResponseContains('Process id not specified');
