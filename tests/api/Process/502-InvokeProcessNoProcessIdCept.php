<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

$I = new ApiTester($scenario);
$I->wantTo('try invoking process, without passing process id');

$chain = $I->getEntityDump('event-chains', 'simple-one-event-mock');
$chain['events'] = [];

$path = '/processes/-/invoke';
$I->signRequestAs('organization', 'POST', $path);

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST($path, []);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(400);
$I->seeResponseContainsJson(['Process not specified']);
