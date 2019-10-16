<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\MultipartStream;
use PHPUnit\Framework\MockObject\MockObject;
use Jasny\DB\Mongo\Collection;
use Jasny\DB\Mongo\DB;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * @covers HttpRequestLogger
 */
class HttpRequestLoggerTest extends \Codeception\Test\Unit
{
    /** @var vfsStreamDirectory */
    protected $root;

    /** @var Collection&MockObject */
    protected $collection;

    /** @var HttpRequestLogger */
    protected $logger;

    public function setUp()
    {
        $this->root = vfsStream::setup('root', null, [
            'hello.txt' => "Hello world",
        ]);

        $this->collection = $this->createMock(Collection::class);
        $db = $this->createConfiguredMock(DB::class, ['selectCollection' => $this->collection]);

        $this->logger = new HttpRequestLogger($db);
    }

    /**
     * @return RequestInterface&MockObject
     */
    protected function createBoringRequest()
    {
        $requestUri = $this->createConfiguredMock(UriInterface::class, ['__toString' => 'https://example.com']);
        $requestBody = $this->createConfiguredMock(StreamInterface::class, ['__toString' => '']);

        /** @var RequestInterface&MockObject $request */
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->any())->method('getUri')->willReturn($requestUri);
        $request->expects($this->any())->method('getMethod')->willReturn('GET');
        $request->expects($this->any())->method('getHeaders')->willReturn([]);
        $request->expects($this->any())->method('getBody')->willReturn($requestBody);
        $request->expects($this->any())->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('');

        return $request;
    }

    /**
     * @return ResponseInterface&MockObject
     */
    protected function createBoringResponse()
    {
        $responseBody = $this->createConfiguredMock(StreamInterface::class, ['__toString' => '']);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->any())->method('getStatusCode')->willReturn(200);
        $response->expects($this->any())->method('getHeaders')->willReturn([]);
        $response->expects($this->any())->method('getBody')->willReturn($responseBody);
        $response->expects($this->any())->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('');

        return $response;
    }

    public function requestProvider()
    {
        $input = ['foo' => 'bar', 'color' => 'red'];

        return [
            'json' => ['application/json', json_encode($input)],
            'urlencoded' => ['application/x-www-form-urlencoded', http_build_query($input)],
        ];
    }

    /**
     * @dataProvider requestProvider
     */
    public function testLogRequest(string $contentType, string $body)
    {
        $uri = 'https://example.com/foo-bar';

        $requestUri = $this->createConfiguredMock(UriInterface::class, ['__toString' => $uri]);
        $requestBody = $this->createConfiguredMock(StreamInterface::class, ['__toString' => $body]);
        $requestHeaders = [
            'Content-Type' => $contentType,
            'X-Custom' => 'abcd'
        ];

        /** @var RequestInterface&MockObject $request */
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())->method('getUri')->willReturn($requestUri);
        $request->expects($this->once())->method('getMethod')->willReturn('POST');
        $request->expects($this->once())->method('getHeaders')->willReturn($requestHeaders);
        $request->expects($this->once())->method('getBody')->willReturn($requestBody);
        $request->expects($this->once())->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn($contentType);

        $response = $this->createBoringResponse();

        $expected = [
            'request' => [
                'uri' => $uri,
                'method' => 'POST',
                'headers' => $requestHeaders,
                'body' => ['foo' => 'bar', 'color' => 'red'],
            ],
            'response' => [
                'status' => 200,
                'headers' => [],
                'body' => '',
            ],
        ];

        $this->collection->expects($this->once())->method('save')->with($expected);

        $this->logger->log($request, $response);
    }

    public function testLogMultipartRequest()
    {
        $uri = 'https://example.com/foo-bar';

        $requestBody = new MultipartStream([
            [
                'name'     => 'foo',
                'contents' => 'bar',
                'headers'  => ['X-Pop' => 'pip'],
            ],
            [
                'name'     => 'color',
                'contents' => 'red',
            ],
            [
                'name'     => 'baz',
                'contents' => fopen('vfs://root/hello.txt', 'r'),
            ],
        ]);

        $requestUri = $this->createConfiguredMock(UriInterface::class, ['__toString' => $uri]);
        $requestHeaders = [
            'Content-Type' => 'multipart/form-data',
            'X-Custom' => 'abcd'
        ];

        /** @var RequestInterface&MockObject $request */
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())->method('getUri')->willReturn($requestUri);
        $request->expects($this->once())->method('getMethod')->willReturn('POST');
        $request->expects($this->once())->method('getHeaders')->willReturn($requestHeaders);
        $request->expects($this->once())->method('getBody')->willReturn($requestBody);
        $request->expects($this->once())->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('multipart/form-data');

        $response = $this->createBoringResponse();

        $expected = [
            'request' => [
                'uri' => $uri,
                'method' => 'POST',
                'headers' => $requestHeaders,
                'body' => [
                    'foo' => 'bar',
                    'color' => 'red',
                    'baz' => 'Hello world',
                ],
            ],
            'response' => [
                'status' => 200,
                'headers' => [],
                'body' => '',
            ],
        ];

        $this->collection->expects($this->once())->method('save')->with($expected);

        $this->logger->log($request, $response);
    }

    /**
     * @dataProvider requestProvider
     */
    public function testLogResponse(string $contentType, string $body)
    {
        $responseBody = $this->createConfiguredMock(StreamInterface::class, ['__toString' => $body]);
        $responseHeaders = [
            'Content-Type' => $contentType,
            'X-Custom' => 'abcd'
        ];

        $request = $this->createBoringRequest();

        /** @var ResponseInterface&MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(299);
        $response->expects($this->once())->method('getHeaders')->willReturn($responseHeaders);
        $response->expects($this->once())->method('getBody')->willReturn($responseBody);
        $response->expects($this->once())->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn($contentType);

        $expected = [
            'request' => [
                'uri' => 'https://example.com',
                'method' => 'GET',
                'headers' => [],
                'body' => '',
            ],
            'response' => [
                'status' => 299,
                'headers' => $responseHeaders,
                'body' => ['foo' => 'bar', 'color' => 'red'],
            ],
        ];

        $this->collection->expects($this->once())->method('save')->with($expected);

        $this->logger->log($request, $response);
    }

    public function testLogMultipartResponse()
    {
        $responseBody = new MultipartStream([
            [
                'name'     => 'foo',
                'contents' => 'bar',
                'headers'  => ['X-Pop' => 'pip'],
            ],
            [
                'name'     => 'color',
                'contents' => 'red',
            ],
            [
                'name'     => 'baz',
                'contents' => fopen('vfs://root/hello.txt', 'r'),
            ],
        ]);

        $responseHeaders = [
            'Content-Type' => 'multipart/form-data',
            'X-Custom' => 'abcd'
        ];

        /** @var ResponseInterface&MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(299);
        $response->expects($this->once())->method('getHeaders')->willReturn($responseHeaders);
        $response->expects($this->once())->method('getBody')->willReturn($responseBody);
        $response->expects($this->once())->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('multipart/form-data');

        $request = $this->createBoringRequest();

        $expected = [
            'request' => [
                'uri' => 'https://example.com',
                'method' => 'GET',
                'headers' => [],
                'body' => '',
            ],
            'response' => [
                'status' => 299,
                'headers' => $responseHeaders,
                'body' => [
                    'foo' => 'bar',
                    'color' => 'red',
                    'baz' => 'Hello world',
                ],
            ],
        ];

        $this->collection->expects($this->once())->method('save')->with($expected);

        $this->logger->log($request, $response);
    }
}
