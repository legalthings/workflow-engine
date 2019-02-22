<?php

use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use LTO\Event;
use LTO\EventChain;

/**
 * @covers LegalEvents
 */
class LegalEventsTest extends \Codeception\Test\Unit
{
    /**
     * Provide data for testing '__construct' method
     *
     * @return array
     */
    public function constructProvider()
    {
        return [
            ['http://www.foo.bar', 'http://www.foo.bar/'],
            ['https://www.foo.bar/path/to/target', 'https://www.foo.bar/path/to/target/'],
            ['http://www.foo.bar/path/', 'http://www.foo.bar/path/']
        ];
    }

    /**
     * Test '__construct' method
     *
     * @dataProvider constructProvider
     */
    public function testConstruct($url, $expectedBaseUrl)
    {
        $client = $this->createMock(HttpClient::class);
        $api = new LegalEvents($client, $url);

        $this->assertAttributeEquals($client, 'client', $api);
        $this->assertAttributeEquals($expectedBaseUrl, 'baseUri', $api);
    }

    /**
     * Test '__construct' method, if exception should be thrown
     *
     * @expectedException ConfigException
     * @expectedExceptionMessage Invalid LegalEvent url 'ftp://some.ftp.com'
     */
    public function testConstructException()
    {
        $client = $this->createMock(HttpClient::class);
        new LegalEvents($client, 'ftp://some.ftp.com');        
    }   

    /**
     * Test 'createRequest' method
     */
    public function testCreateRequest()
    {
        $chain = $this->getEventChainMock();
        $api = $this->createPartialMock(LegalEvents::class, []);

        $result = $api->createRequest($chain);
        $body = $result->getBody();

        $this->assertInstanceOf(Request::class, $result);
        $this->assertSame('POST', $result->getMethod());
        $this->assertEquals(['Content-Type' => ['application/json']], $result->getHeaders());

        $expectedBody = '{"id":"foo_id","events":[{"body":"foo","timestamp":null,"previous":null,"signkey":null,"signature":null,"hash":null},{"body":"bar","timestamp":null,"previous":null,"signkey":null,"signature":null,"hash":null}]}';

        $this->assertInstanceOf(StreamInterface::class, $body);
        $this->assertSame($expectedBody, (string)$body);
    }

    /**
     * Test 'send' method
     */
    public function testSend()
    {
        $client = $this->createMock(HttpClient::class);
        $request = $this->createMock(Request::class);

        $api = new LegalEvents($client, 'http://www.foo');        

        $client->expects($this->once())->method('send')->with($request, ['base_uri' => 'http://www.foo/']);

        $api->send($request);
    }

    /**
     * Test 'send' method, if exception should be thrown
     *
     * @expectedException Exception
     * @expectedExceptionMessage Failed to send message to legalevents service
     */
    public function testSendException()
    {
        $client = $this->createMock(HttpClient::class);
        $request = $this->createMock(Request::class);

        $api = new LegalEvents($client, 'http://www.foo');        

        $client->expects($this->once())->method('send')->with($request, ['base_uri' => 'http://www.foo/'])
            ->will($this->returnCallback(function() use ($request) {
                throw new ClientException('Some client exception', $request);
            }));

        $api->send($request);   
    }

    /**
     * Get event chain mock
     *
     * @return EventChain
     */
    protected function getEventChainMock()
    {
        $chain = $this->createMock(EventChain::class);        

        $events = [
            $this->createMock(Event::class),
            $this->createMock(Event::class)
        ];

        $events[0]->body = 'foo';
        $events[1]->body = 'bar';

        $chain->id = 'foo_id';
        $chain->events = $events;

        return $chain;
    }
}
