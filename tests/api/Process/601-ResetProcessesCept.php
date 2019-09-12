<?php

$I = new ApiTester($scenario);
$I->wantTo('remove all processes');

$I->signRequestAs('user', 'DELETE', '/processes');
$I->sendDELETE('/processes/');

$I->seeResponseCodeIs(204);

// ---
$I->expect("All processes are removed");

$I->signRequestAs('user', 'GET', '/processes');
$I->sendGET('/processes/');

$I->seeResponseIsJson();
$I->seeResponseCodeIs(200);

$I->seeResponseIsProcessListWith([]);

// ---
$I->expect("Processes of other users are not removed");

$I->signRequest('GET', '/processes/8fd21874-c3f3-11e9-91e2-237252395578');
$I->sendGET('/processes/8fd21874-c3f3-11e9-91e2-237252395578');

$I->seeResponseCodeIs(200);
$I->seeResponseContainsJson(["id" => "8fd21874-c3f3-11e9-91e2-237252395578"]);
