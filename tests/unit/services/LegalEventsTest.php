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
        $data = [
            "id" => "foo_id",
            "events" => [
                [
                    "body" => "foo",
                    "hash" => '12345',
                ],
                [
                    "body" => "bar",
                    "hash" => '67890',
                ]
            ]
        ];

        $chain = $this->createMock(EventChain::class);
        $chain->expects($this->once())->method('jsonSerialize')
            ->willReturn($data);

        $httpClient = $this->createMock(HttpClient::class);

        $api = new LegalEvents($httpClient, 'http://example.com');

        $result = $api->createRequest($chain);
        $body = $result->getBody();

        $this->assertInstanceOf(Request::class, $result);
        $this->assertSame('POST', $result->getMethod());
        $this->assertEquals(['Content-Type' => ['application/json']], $result->getHeaders());

        $this->assertInstanceOf(StreamInterface::class, $body);
        $this->assertSame($data, json_decode((string)$body, true));
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
}
