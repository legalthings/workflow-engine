<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

$I = new ApiTester($scenario);
$I->wantTo('invoke process, if no action can be performed');

$chain = $I->getEntityDump('event-chains', 'simple-one-event-mock');
$chain['events'] = [];

// Fetch event chain
$I->expectHttpRequest(function (Request $request) use ($I, $chain) {
    $I->assertEquals('http://foo-event-chain-service/event-chains/' . $chain['id'], (string)$request->getUri());
    
    return new Response(200, ['Content-Type' => 'application/json'], json_encode($chain));
});

$path = '/processes/cad2f7fd-8d1d-410d-8ae4-c60c0aaf05e4/invoke';
$I->signRequest('POST', $path);

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST($path, ['id' => 'cad2f7fd-8d1d-410d-8ae4-c60c0aaf05e4']);

$I->seeResponseCodeIs(204);
