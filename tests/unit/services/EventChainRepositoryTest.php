<?php

use PHPUnit\Framework\MockObject\MockObject;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\Psr7\Response as HttpResponse;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use LTO\Event;
use LTO\EventChain;
use LTO\Account;

/**
 * @covers \EventChainRepository
 */
class EventChainRepositoryTest extends \Codeception\Test\Unit
{
    /**
     * @var callable
     **/
    protected $createEvent;

    /**
     * @var LTO\Account
     **/
    protected $account;

    /**
     * Perform some actions before each test
     */
    public function _before()
    {
        $this->createEvent = function() {};
        $this->account = $this->createMock(Account::class);
    }
    /**
     * Create a Guzzle mock handler
     *
     * @param Response[] $responses
     * @param array      $history    OUTPUT
     */
    protected function createGuzzleMock(array $responses, &$history = null): HttpClient
    {
        $mock = new MockHandler($responses);

        $handler = HandlerStack::create($mock);

        if (func_num_args() > 1) {
            $history = [];
            $handler->push(Middleware::history($history));
        }

        return new HttpClient(['handler' => $handler]);
    }

    /**
     * @return EventChain&MockObject
     */
    protected function createEventChainMock(int $eventCount = 2, string $seed = ''): EventChain
    {
        $chain = $this->createMock(EventChain::class);
        $chain->id = base58_encode(hash('sha256', 'chain' . $seed, true));
        $initial = base58_encode(hash('sha256', $seed, true));

        $chain->events = [];
        $previous = $initial;

        for ($i = 0; $i < $eventCount; $i++) {
            $chain->events[$i] = $this->createMock(Event::class);
            $chain->events[$i]->previous = $previous;
            $chain->events[$i]->hash = base58_encode(hash('sha256', $seed . $i, true));

            $previous = $chain->events[$i]->hash;
        }

        $chain->expects($this->any())->method('getInitialHash')->willReturn($initial);
        $chain->expects($this->any())->method('getLatestHash')->willReturn($previous);

        return $chain;
    }


    public function testRegisterAndGet()
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->never())->method($this->anything());

        $chain = $this->createEventChainMock();

        $repository = new EventChainRepository($this->createEvent, $this->account, $client);
        $repository->register($chain);

        $result = $repository->get($chain->id);

        $this->assertEquals($result, $chain);

        // Check that the event chain is cloned
        $this->assertNotSame($result, $chain);
    }

    public function fetchMethodProvider()
    {
        return [
            ['fetch'],
            ['get'],
        ];
    }

    /**
     * @dataProvider fetchMethodProvider
     */
    public function testFetch(string $method)
    {
        $chainResponseBody = [
            'id' => 'JEKNVnkbo3jqSHT8tfiAKK4tQTFK7jbx8t18wEEnygya',
            'latest_hash' => 'J26EAStUDXdRUMhm1UcYXUKtJWTkcZsFpxHRzhkStzbS',
        ];

        $client = $this->createGuzzleMock([
            new HttpResponse(200, ['Content-Type' => 'application/json'], json_encode($chainResponseBody))
        ], $history);

        $repository = new EventChainRepository($this->createEvent, $this->account, $client);

        $chain = $repository->$method('JEKNVnkbo3jqSHT8tfiAKK4tQTFK7jbx8t18wEEnygya');

        $this->assertInstanceOf(EventChain::class, $chain);
        $this->assertEquals('JEKNVnkbo3jqSHT8tfiAKK4tQTFK7jbx8t18wEEnygya', $chain->id);
        $this->assertEquals('J26EAStUDXdRUMhm1UcYXUKtJWTkcZsFpxHRzhkStzbS', $chain->getLatestHash());

        $this->assertCount(1, $history);

        /** @var HttpRequest $request */
        $request = $history[0]['request'];

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('event-chains/JEKNVnkbo3jqSHT8tfiAKK4tQTFK7jbx8t18wEEnygya', (string)$request->getUri());
    }

    /**
     * Test 'fetch' method, if json decode error happened
     *
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Received invalid JSON from event chain service. Control character error, possibly incorrectly encoded
     */
    public function testFetchJsonError()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(200, ['Content-Type' => 'application/json'], '{"id": "JEKNVnkbo3jqSHT8tfiAKK4tQTFK7')
        ], $history);

        $repository = new EventChainRepository($this->createEvent, $this->account, $client);
        $repository->fetch('JEKNVnkbo3jqSHT8tfiAKK4tQTFK7jbx8t18wEEnygya');
    }

    /**
     * Test 'fetch' method, if json returned does not contain chain id
     *
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage 'id' property is missing
     */
    public function testFetchNoId()
    {
        $chainResponseBody = [
            'latest_hash' => 'J26EAStUDXdRUMhm1UcYXUKtJWTkcZsFpxHRzhkStzbS'
        ];

        $client = $this->createGuzzleMock([
            new HttpResponse(200, ['Content-Type' => 'application/json'], json_encode($chainResponseBody))
        ], $history);

        $repository = new EventChainRepository($this->createEvent, $this->account, $client);
        $repository->fetch('JEKNVnkbo3jqSHT8tfiAKK4tQTFK7jbx8t18wEEnygya');
    }

    /**
     * Test 'fetch' method, if json returned does not contain chain id
     *
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage 'latest_hash' property is missing
     */
    public function testFetchNoLatestHash()
    {
        $chainResponseBody = [
            'id' => 'JEKNVnkbo3jqSHT8tfiAKK4tQTFK7jbx8t18wEEnygya'
        ];

        $client = $this->createGuzzleMock([
            new HttpResponse(200, ['Content-Type' => 'application/json'], json_encode($chainResponseBody))
        ], $history);

        $repository = new EventChainRepository($this->createEvent, $this->account, $client);
        $repository->fetch('JEKNVnkbo3jqSHT8tfiAKK4tQTFK7jbx8t18wEEnygya');
    }

    public function testUpdateAndGet()
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->never())->method($this->anything());

        $repository = new EventChainRepository($this->createEvent, $this->account, $client);

        $chain = $this->createEventChainMock(2);
        $repository->register($chain);

        $newChain = $this->createEventChainMock(3);
        $repository->update($newChain);

        $result = $repository->get($chain->id);

        $this->assertEquals($result, $newChain);

        // Check that the event chain is cloned
        $this->assertNotSame($result, $chain);
        $this->assertNotSame($result, $newChain);
    }

    /**
     * Test 'update' method, if chain is not registered
     *
     * @expectedException BadMethodCallException
     * @expectedExceptionMessageRegExp /Chain '[0-9a-zA-Z]+' is not registered with the repository/
     */
    public function testUpdateNotRegistered()
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->never())->method($this->anything());

        $repository = new EventChainRepository($this->createEvent, $this->account, $client);

        $chain = $this->createEventChainMock(2);
        $repository->update($chain);        
    }

    public function testPersist()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(201)
        ], $history);

        $repository = new EventChainRepository($this->createEvent, $this->account, $client);

        $chain = $this->createEventChainMock(2);
        $repository->register($chain);

        $newChain = $this->createEventChainMock(3);
        $repository->update($newChain);

        $partialChain = clone $newChain;
        $partialChain->events = [$newChain->events[2]];

        $newChain->expects($this->once())->method('getPartialAfter')
            ->with($newChain->events[1]->hash)
            ->willReturn($partialChain);

        $data = [
            'id' => $chain->id,
            'events' => [
                [
                    'body' => 'A54BREAPQiWqZo3k9RQJ1U4yZBjyDj37aciJMiAJfNACHVoZVDYi3Q2qhqE',
                    'hash' => $newChain->events[2]->hash, // Not actually the hash of contents, but doesn't matter
                    'timestamp' => (new DateTime('2018-01-01T00:00:00+00:00'))->getTimestamp(),
                    'previous' => '72gRWx4C1Egqz9xvUBCYVdgh7uLc5kmGbjXFhiknNCTW',
                    'signkey' => '8TxFbgGPKVhuauHJ47vn3C74eVugAghTGou35Wtd51Mj',
                    'signature' => '3pkDcJ9gvT5iXy5F9DkVgv79nPrq8r24EK7ih1ibKszyohn6sgBJx8E5mpCXkm9HyUJjhV1dspUW6mrpuMj5CQjK',
                ],
            ],
        ];
        $partialChain->expects($this->once())->method('jsonSerialize')->willReturn($data);

        $repository->persist($chain->id);

        $this->assertCount(1, $history);

        /** @var HttpRequest $request */
        $request = $history[0]['request'];

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('queue', (string)$request->getUri());
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));

        $this->assertEquals($data, json_decode((string)$request->getBody(), true));
    }

    public function testPersistWithUnchangedChain()
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->never())->method($this->anything());

        $repository = new EventChainRepository($this->createEvent, $this->account, $client);

        $partialChain = $this->createEventChainMock(2);
        $partialChain->events = [];

        $chain = $this->createEventChainMock(2);
        $chain->expects($this->once())->method('getPartialAfter')
            ->with($chain->events[1]->hash)
            ->willReturn($partialChain);

        $repository->register($chain);

        $repository->persist($chain->id);
    }

    public function testPersistAll()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(201),
            new HttpResponse(201),
            new HttpResponse(201),
        ], $history);

        $repository = new EventChainRepository($this->createEvent, $this->account, $client);

        $chains = [];

        $unchangedChain = $this->createEventChainMock(3, 'unchanged');
        $unchangedPartial = clone $unchangedChain;
        $unchangedPartial->events = [];
        $unchangedChain->expects($this->once())->method('getPartialAfter')->willReturn($unchangedPartial);
        $repository->register($unchangedChain);

        for ($i = 0; $i < 3; $i++) {
            $chain = $this->createEventChainMock(0, 'chain' . $i);
            $partialChain = $this->createEventChainMock(1, 'chain' . $i);

            $chain->expects($this->once())->method('getPartialAfter')
                ->with($chain->getInitialHash())
                ->willReturn($partialChain);

            $partialChain->expects($this->once())->method('jsonSerialize')
                ->willReturn((object)[
                    'id' => $chain->id,
                    'events' => [
                        (object)['hash' => $partialChain->events[0]->hash],
                    ],
                    'latest_hash' => $partialChain->events[0]->hash,
                ]);

            $repository->register($chain);
            $chains[$i] = $partialChain;
        }

        $repository->persistAll();

        $this->assertCount(3, $history);

        /** @var HttpRequest $request */
        foreach ($history as $i => ['request' => $request]) {
            $this->assertEquals('POST', $request->getMethod());
            $this->assertEquals('queue', (string)$request->getUri());
            $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));

            $expected = [
                'id' => $chains[$i]->id,
                'events' => [
                    ['hash' => $chains[$i]->events[0]->hash],
                ],
                'latest_hash' => $chains[$i]->events[0]->hash,
            ];
            $this->assertEquals($expected, json_decode((string)$request->getBody(), true));
        }
    }

    /**
     * Test 'persist' method, if chain is not registered
     *
     * @expectedException BadMethodCallException
     * @expectedExceptionMessageRegExp /Chain '[0-9a-zA-Z]+' is not registered with the repository/
     */
    public function testPersistNotRegistered()
    {
        $client = $this->createMock(HttpClient::class);
        $client->expects($this->never())->method($this->anything());

        $repository = new EventChainRepository($this->createEvent, $this->account, $client);

        $chain = $this->createEventChainMock(2);
        $repository->persist($chain->id);        
    }
}
