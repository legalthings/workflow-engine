<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

$I = new ApiTester($scenario);
$I->wantTo('try invoking process, if process id in params does not match id in url');

$chain = $I->getEntityDump('event-chains', 'simple-one-event-mock');
$chain['events'] = [];

$path = '/processes/98kgh356-108e-fk69-8d2d-7914ffddf45h/invoke';
$I->signRequestAs('organization', 'POST', $path);

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST($path, ['process' => '18kgh356-108e-fk69-8d2d-7914ffddf45h']);

$I->seeResponseIsJson();
$I->seeResponseCodeIs(400);
$I->seeResponseContainsJson(['Incorrect process id']);
