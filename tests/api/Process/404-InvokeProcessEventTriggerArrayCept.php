<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

$I = new ApiTester($scenario);
$I->wantTo('Invoke process, to see if event trigger works correctly, handling array of events');

$chain = $I->getEntityDump('event-chains', 'simple-one-event-mock');
$chain['events'] = [];

// Fetch event chain
$I->expectHttpRequest(function (Request $request) use ($I, $chain) {
    $I->assertEquals('http://foo-event-chain-service/event-chains/' . $chain['id'], (string)$request->getUri());
    
    return new Response(200, ['Content-Type' => 'application/json'], json_encode($chain));
});

$path = '/processes/3e5c7uy5-108e-fk69-8d2d-7914ffd23w6u/invoke';
$I->signRequestAs('organization', 'POST', $path);

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST($path, ['id' => '3e5c7uy5-108e-fk69-8d2d-7914ffd23w6u']);

$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

$I->seeResponseContainsJson(['id' => $chain['id']]);
$I->seeResponseChainEventsCount(3);

$I->seeResponseChainEventHasBody(0, [
    '$schema' => 'https://specs.livecontracts.io/v0.2.0/identity/schema.json#',
    'key' => 'foo_identity',
    'node' => 'localhost',
    'signkeys' => [
        'user' => 'foo',
        'system' => 'bar',
    ]
]);
$I->seeResponseChainEventHasBody(1, [
    '$schema' => 'https://specs.livecontracts.io/v0.2.0/identity/schema.json#',
    'key' => 'bar_identity',
    'node' => 'localhost',
    'signkeys' => [
        'user' => 'foo_bar',
        'system' => 'bar_baz',
    ],
    'encryptkey' => 'zoo'
]);

$I->seeResponseChainEventContainsJson(2, ['action' => ['key' => 'step1']]);
