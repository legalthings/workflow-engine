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
$I->seeHttpHeader('Content-Type', 'application/json; charset=utf-8');

$I->seeResponseContainsJson(['id' => '2c83KDmRCJwaKWky1jmtTRYmsgXAhmuDC8P12KpqqbrQKkY6UMECmKeZE5m8Rx']);
$I->seeResponseMatchesJsonType([
    'id' => 'string',
    'events' => 'array',
    'latest_hash' => 'string',
]);

$expectedEventTypes = [
    'body' => 'string',
    'timestamp' => 'integer',
    'previous' => 'string',
    'signkey' => 'string',
    'signature' => 'string',
    'hash' => 'string'
];
$I->seeResponseMatchesJsonType($expectedEventTypes, '$.events[0]');
$I->seeResponseMatchesJsonType($expectedEventTypes, '$.events[1]');

[$events] = $I->grabDataFromResponseByJsonPath('$.events');
[$latestHash] = $I->grabDataFromResponseByJsonPath('$.latest_hash');

$I->assertCount(2, $events);

$I->expect("the events to form a proper hash chain");
$I->assertEquals('HuT1ts2iRt9tPUmbQDLJDjgYWaeHBR1B14TePYtwqSS5', $events[0]['previous']);
$I->assertEquals($events[0]['hash'], $events[1]['previous']);
$I->assertEquals($events[1]['hash'], $latestHash);

$I->expect("both events to be signed by the node");
$I->assertEquals("3UDCFY6MojrPKaayHgAEqrnp99JhviSAiraJX8J1fJ9E", $events[0]['signkey']);
$I->assertEquals("3UDCFY6MojrPKaayHgAEqrnp99JhviSAiraJX8J1fJ9E", $events[1]['signkey']);

$I->expect("the event bodies to be an identity and a response");

$expectedBody1 = [
    '$schema' => 'https://specs.livecontracts.io/v0.2.0/identity/schema.json#',
    'key' => 'foo_identity',
    'node' => 'localhost',
    'signkeys' => [
        'user' => 'foo',
        'system' => 'bar',
    ],
];
$I->assertEquals($expectedBody1, json_decode(base58_decode($events[0]['body']), true));

$expectedBody2 = [
    'process' => '98kgh356-108e-fk69-8d2d-7914ffddf45h',
    '$schema' => 'https://specs.livecontracts.io/v0.2.0/response/schema.json#',
    'key' => 'ok',
    'action' => [
        'key' => 'step1',
    ],
];
$I->assertArraySubset($expectedBody2, json_decode(base58_decode($events[1]['body']), true));

$I->expect("data instructions to be applied");
$I->assertNotContains("<ref>", base58_decode($events[1]['body']));
