<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

$I = new ApiTester($scenario);
$I->wantTo('Invoke process, to see if event trigger works correctly');

$chain = $I->getEntityDump('event-chains', 'simple-one-event-mock');
$chain['events'] = [];

// Fetch event chain
$I->expectHttpRequest(function (Request $request) use ($I, $chain) {
    $I->assertEquals('http://foo-event-chain-service/event-chains/' . $chain['id'], (string)$request->getUri());
    
    return new Response(200, ['Content-Type' => 'application/json'], json_encode($chain));
});

$path = '/processes/98kgh356-108e-fk69-8d2d-7914ffddf45h/invoke';
$I->signRequestAs('organization', 'POST', $path);

$I->haveHttpHeader('Content-Type', 'application/json');
$I->sendPOST($path, ['id' => '98kgh356-108e-fk69-8d2d-7914ffddf45h']);

$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();

$I->seeResponseContainsJson(['id' => $chain['id']]);
$I->seeResponseChainEventsCount(2);

$I->seeResponseChainEventHasBody(0, [
    '$schema' => 'https://specs.livecontracts.io/v0.2.0/identity/schema.json#',
    'key' => 'foo_identity',
    'node' => 'localhost',
    'signkeys' => [
        'user' => 'foo',
        'system' => 'bar',
    ]
]);

$I->seeResponseChainEventContainsJson(1, ['action' => ['key' => 'step1']]);
