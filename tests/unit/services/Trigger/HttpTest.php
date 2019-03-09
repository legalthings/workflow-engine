<?php

namespace Trigger;

use Jasny\TestHelper;
use JmesPath\Env as JmesPath;
use Psr\Container\ContainerInterface;
use Trigger\Http as HttpTrigger;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response as HttpResponse;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

/**
 * @covers \Trigger\Http
 * @covers \Trigger\AbstractTrigger
 */
class HttpTest extends \Codeception\Test\Unit
{
    use TestHelper;

    /**
     * @var \DataPatcher
     */
    protected $patcher;

    /**
     * @var callable
     */
    protected $jmespath;

    public function _before()
    {
        $this->jmespath = JmesPath::createRuntime();
        $this->patcher = new \DataPatcher($this->jmespath);
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

    public function testInvoke()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(200, [], "Test message"),
        ], $history);

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $process = new \Process();
        $action = (new \Action)->setValues([
            'url' => 'http://example.com',
            'headers' => ['X-Foo' => 'bar'],
        ]);

        $response = ($trigger)($process, $action);

        $this->assertEquals('ok', $response->key);
        $this->assertEquals('Test message', $response->data);
        
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('http://example.com', $request->getUri());
        $this->assertContains('bar', $request->getHeader('X-Foo'));
    }

    public function testInvokeWithJsonResponse()
    {
        $data = (object)['color' => 'red', 'type' => 'hammer'];

        $client = $this->createGuzzleMock([
            new HttpResponse(200, ['Content-Type' => 'application/json'], json_encode($data)),
        ], $history);

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $process = new \Process();
        $action = (new \Action)->setValues([
            'url' => 'http://example.com',
        ]);

        $response = ($trigger)($process, $action);

        $this->assertEquals('ok', $response->key);
        $this->assertEquals($data, $response->data);

        $this->assertCount(1, $history);
    }

    public function testInvokeDeferred()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(202)
        ], $history);

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $process = new \Process();
        $action = (new \Action)->setValues([
            'url' => 'http://example.com',
        ]);

        $response = ($trigger)($process, $action);

        $this->assertNull($response);

        $this->assertCount(1, $history);
    }

    public function testInvokeClientError()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(400, [], "Something is wrong")
        ]);

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $process = new \Process();
        $action = (new \Action)->setValues([
            'url' => 'http://example.com',
        ]);

        $response = ($trigger)($process, $action);

        $this->assertEquals('error', $response->key);
        $this->assertEquals("Something is wrong", $response->data);
    }

    public function testInvokeClientErrorAsJson()
    {
        $data = ['A is wrong', 'B is wrong'];

        $client = $this->createGuzzleMock([
            new HttpResponse(400, ['Content-Type' => 'application/json'], json_encode($data))
        ]);

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $process = new \Process();
        $action = (new \Action)->setValues([
            'url' => 'http://example.com',
        ]);

        $response = ($trigger)($process, $action);

        $this->assertEquals('error', $response->key);
        $this->assertEquals($data, $response->data);
    }

    public function testInvokeServerError()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(500, [], "Something is wrong")
        ]);

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $process = new \Process();
        $process->id = '00000000-0000-0000-0000-000000000000';

        $action = (new \Action)->setValues([
            'key' => 'foo',
            'url' => 'http://example.com',
        ]);

        $response = @($trigger)($process, $action);
        $this->assertEquals('error', $response->key);
        $this->assertEquals('Unexpected error', $response->data);

        $this->assertLastError(
            \E_USER_WARNING,
            "Unexpected error on HTTP request for action 'foo' of process '00000000-0000-0000-0000-000000000000'. " .
            "Server error: `GET http://example.com` resulted in a `500 Internal Server Error` response:\n" .
            "Something is wrong\n"
        );
    }

    public function testWithConfig()
    {
        $client = $this->createGuzzleMock([]);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $config = (object)['projection' => 'wop', 'url' => 'http://example.com', 'foo' => 'bar'];
        $configuredTrigger = $trigger->withConfig($config, $container);
        $this->assertNotSame($trigger, $configuredTrigger);

        $sameTrigger = $configuredTrigger->withConfig(['projection' => 'wop'], $container);
        $this->assertSame($sameTrigger, $configuredTrigger);
    }

    public function testInvokeWithQuery()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(200),
        ], $history);

        $container = $this->createMock(ContainerInterface::class);

        $trigger = (new HttpTrigger($client, $this->patcher, $this->jmespath))
            ->withConfig(['query' => ['color' => 'blue', 'shape' => 'square']], $container);

        $process = new \Process();
        $action = (new \Action)->setValues([
            'url' => 'http://example.com',
            'query' => ['color' => 'red', 'type' => 'hammer'],
        ]);

        ($trigger)($process, $action);

        $this->assertCount(1, $history);
        $this->assertEquals(
            'http://example.com?color=red&shape=square&type=hammer',
            (string)$history[0]['request']->getUri()
        );
    }

    public function authProvider()
    {
        return [
            [null, 'john:secret'],
            [['username' => 'jane', 'password' => '1234'], 'jane:1234'],
            [(object)['username' => 'jane', 'password' => '1234'], 'jane:1234'],
        ];
    }

    /**
     * @dataProvider authProvider
     */
    public function testInvokeWithAuth($auth, $expected)
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(200),
        ], $history);

        $container = $this->createMock(ContainerInterface::class);

        $trigger = (new HttpTrigger($client, $this->patcher, $this->jmespath))
            ->withConfig(['auth' => ['username' => 'john', 'password' => 'secret']], $container);

        $process = new \Process();
        $action = (new \Action)->setValues([
            'url' => 'http://example.com',
            'auth' => $auth,
        ]);

        ($trigger)($process, $action);

        $this->assertCount(1, $history);
        $request = $history[0]['request'];
        $this->assertEquals("Basic " . base64_encode($expected), $request->getHeaderLine('Authorization'));
    }


    public function testInvokeWithTextData()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(200),
        ], $history);

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $process = new \Process();
        $action = (new \Action)->setValues([
            'url' => 'http://example.com',
            'data' => 'Test data',
        ]);

        ($trigger)($process, $action);

        $this->assertCount(1, $history);
        $request = $history[0]['request'];

        $this->assertContains("text/plain", $request->getHeader('Content-Type'));
        $this->assertEquals("Test data", $request->getBody());
    }

    public function testInvokeWithJsonData()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(200),
        ], $history);

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $process = new \Process();
        $action = (new \Action)->setValues([
            'url' => 'http://example.com',
            'data' => ['color' => 'red', 'type' => 'hammer'],
        ]);

        ($trigger)($process, $action);

        $this->assertCount(1, $history);
        $request = $history[0]['request'];

        $this->assertContains("application/json", $request->getHeader('Content-Type'));

        $expect = json_encode(['color' => 'red', 'type' => 'hammer']);
        $this->assertJsonStringEqualsJsonString($expect, (string)$request->getBody());
    }

    public function testInvokeWithUrlEncodedData()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(200),
        ], $history);

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $process = new \Process();
        $action = (new \Action)->setValues([
            'url' => 'http://example.com',
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'data' => ['color' => 'red', 'type' => 'hammer'],
        ]);

        ($trigger)($process, $action);

        $this->assertCount(1, $history);
        $request = $history[0]['request'];

        $this->assertEquals("application/x-www-form-urlencoded", $request->getHeaderLine('Content-Type'));

        $expect = http_build_query(['color' => 'red', 'type' => 'hammer']);
        $this->assertEquals($expect, (string)$request->getBody());
    }

    public function testInvokeWithMultipartFormData()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(200),
        ], $history);

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $process = new \Process();
        $action = (new \Action)->setValues([
            'url' => 'http://example.com',
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'data' => [
                [
                    'name'     => 'foo',
                    'contents' => 'once upon a time'
                ],
                [
                    'name'     => 'baz',
                    'contents' => 'in the wild west'
                ],
            ]
        ]);

        ($trigger)($process, $action);

        $this->assertCount(1, $history);

        $this->assertContains("multipart/form-data", $history[0]['request']->getHeader('Content-Type')[0]);
        $this->assertContains("boundary", $history[0]['request']->getHeader('Content-Type')[0]);

        $body = (string)$history[0]['request']->getBody();
        $this->assertContains('name="foo"', $body);
        $this->assertContains("once upon a time", $body);
        $this->assertContains('name="baz"', $body);
        $this->assertContains("in the wild west", $body);
    }

    public function testInvokeConcurrent()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(200, [], "foo"),
            new HttpResponse(200, [], "dog"),
            new HttpResponse(200, ['Content-Type' => 'application/json'],'{"I":"one","II":"two"}'),
            new HttpResponse(202),
        ], $history);

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $data = [
            'food' => ['fish', 'chicken', 'spaghetti'],
            'year' => 2016,
        ];

        $process = new \Process();
        $action = (new \Action)->setValues([
            'headers' => ['X-Foo' => 'bar'],
            'query' => ['view' => 'page'],
            'data' => $data,
            'requests' => [
                [
                    'url' => 'http://example.com/foo',
                ],
                [
                    'url' => 'http://example.com/dog',
                    'headers' => ['X-Dog' => 'cat'],
                    'query' => ['list' => 'all'],
                ],
                'more' => [
                    'url' => 'http://example.com/brain',
                    'method' => 'POST',
                    'data' => ['color' => 'red'],
                ],
                // Deferred request, not in response data.
                'defer' => [
                    'url' => 'http://example.com/defer',
                ]
            ],
        ]);

        $response = ($trigger)($process, $action);
        $this->assertEquals('ok', $response->key);
        $this->assertEquals(['foo', 'dog', 'more' => (object)['I' => 'one', 'II' => 'two']], $response->data);

        $this->assertCount(4, $history);

        $payload1 = json_decode((string)$history[0]['request']->getBody(), true);
        $this->assertEquals('GET', $history[0]['request']->getMethod());
        $this->assertEquals('http://example.com/foo?view=page', (string)$history[0]['request']->getUri());
        $this->assertEquals('bar', $history[0]['request']->getHeaderLine('X-Foo'));
        $this->assertSame($data, $payload1);

        $payload2 = json_decode((string)$history[1]['request']->getBody(), true);
        $this->assertEquals('GET', $history[1]['request']->getMethod());
        $this->assertEquals('http://example.com/dog?view=page&list=all', (string)$history[1]['request']->getUri());
        $this->assertEquals('bar', $history[0]['request']->getHeaderLine('X-Foo'));
        $this->assertEquals('cat', $history[1]['request']->getHeaderLine('X-Dog'));
        $this->assertSame($data, $payload2);

        $payload3 = json_decode((string)$history[2]['request']->getBody(), true);
        $this->assertEquals('POST', $history[2]['request']->getMethod());
        $this->assertEquals('http://example.com/brain?view=page', (string)$history[2]['request']->getUri());
        $this->assertEquals('bar', $history[0]['request']->getHeaderLine('X-Foo'));
        $this->assertSame($data + ['color' => 'red'], $payload3);

        $payload4 = json_decode((string)$history[3]['request']->getBody(), true);
        $this->assertEquals('GET', $history[3]['request']->getMethod());
        $this->assertEquals('http://example.com/defer?view=page', (string)$history[3]['request']->getUri());
    }

    public function testInvokeConcurrentWithErrors()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(200, [], "foo"),
            new HttpResponse(400, ['Content-Type' => 'application/json'],'{"I":"one","II":"two"}'),
            new HttpResponse(404, [], "thing not found"),
            new HttpResponse(500, [], "Something is wrong"),
        ], $history);

        $trigger = new HttpTrigger($client, $this->patcher, $this->jmespath);

        $process = new \Process();
        $process->id = '00000000-0000-0000-0000-000000000000';

        $action = (new \Action)->setValues([
            'key' => 'foo',
            'requests' => [
                'good' => [
                    'url' => 'http://example.com/foo',
                ],
                'not_good' => [
                    'url' => 'http://example.com/bad',
                ],
                'not_here' => [
                    'url' => 'http://example.com/notfound',
                ],
                'broken' => [
                    'url' => 'http://example.com/err',
                ]
            ],
        ]);

        $response = @($trigger)($process, $action);
        $this->assertEquals('error', $response->key);
        $this->assertEquals([
            'good' => 'foo',
            ':errors' => [
                'not_good' => (object)['I' => 'one', 'II' => 'two'],
                'not_here' => 'thing not found',
                'broken' => 'Unexpected error'
            ],
        ], $response->data);

        $this->assertCount(4, $history);

        $this->assertLastError(
            \E_USER_WARNING,
            "Unexpected error on HTTP request for action 'foo' of process '00000000-0000-0000-0000-000000000000'. " .
            "Server error: `GET http://example.com/err` resulted in a `500 Internal Server Error` response:\n" .
            "Something is wrong\n"
        );
    }

    public function testProject()
    {
        $client = $this->createGuzzleMock([
            new HttpResponse(200, [], "Test message"),
        ], $history);

        $container = $this->createMock(ContainerInterface::class);
        $trigger = (new HttpTrigger($client, $this->patcher, $this->jmespath))
            ->withConfig([
                'url' => 'http://example.com',
                'projection' => '{query: {color: name, type: shape}}'
            ], $container);

        $process = new \Process();

        $action = \Action::fromData([
            'key' => 'foo',
            'name' => 'red',
            'shape' => 'hammer',
        ]);

        $response = $trigger($process, $action);

        $this->assertEquals('ok', $response->key);
        $this->assertEquals('Test message', $response->data);

        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertEquals('GET', $request->getMethod());

        $expect = 'http://example.com?' . http_build_query(['color' => 'red', 'type' => 'hammer']);
        $this->assertEquals($expect, (string)$request->getUri());
    }
}
