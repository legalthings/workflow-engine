<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers HttpRequestLog
 */
class HttpRequestLogTest extends \Codeception\Test\Unit
{
    /**
     * Provide data for testing 'cast' method
     *
     * @return array
     */
    public function castProvider()
    {
        $requestContent = ['some1' => 'value1'];
        $responseContent = ['some2' => 'value2'];
        $requestJson = json_encode($requestContent);
        $responseJson = json_encode($responseContent);

        return [
            ['application/json', $requestJson, $requestContent, 'application/json', $responseJson, $responseContent],
            ['foo;application/json; bar', $requestJson, $requestContent, 'foo;application/json; baz', $responseJson, $responseContent],
            ['application/x-www-form-urlencoded', $requestJson, $requestContent, 'application/json', $responseJson, $responseContent, true],
            ['foo;application/x-www-form-urlencoded;bar', $requestJson, $requestContent, 'application/json', $responseJson, $responseContent, true],
            ['multipart/form-data', $requestJson, $requestContent, 'application/json', $responseJson, $responseContent, true],
            ['foo;multipart/form-data;bar', $requestJson, $requestContent, 'application/json', $responseJson, $responseContent, true],
            ['text/plain', 'Some content', 'Some content', 'text/plain', 'Another content', 'Another content'],
        ];
    }

    /**
     * Test 'cast' method
     *
     * @dataProvider castProvider
     */
    public function testCast(
        $requestContentType, 
        $requestContent, 
        $expectedRequestContent, 

        $responseContentType, 
        $responseContent, 
        $expectedResponseContent, 

        $useParsedBody = false
    ) {
        $url = 'http://foo-bar.com';
        $method = 'POST';
        $requestHeaders = ['zoo1' => 'baz1'];
        $responseHeaders = ['zoo2' => 'baz2'];
        $code = 200;

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uriObject = $this->createMock(UriInterface::class);
        $requestBody = $this->createMock(StreamInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $request->expects($this->once())->method('getUri')->willReturn($uriObject);
        $uriObject->expects($this->once())->method('__toString')->willReturn($url);
        $request->expects($this->once())->method('getMethod')->willReturn($method);
        $request->expects($this->once())->method('getHeaders')->willReturn($requestHeaders);
        $request->expects($this->once())->method('getBody')->willReturn($requestBody);        
        $requestBody->expects($this->once())->method('__toString')->willReturn($requestContent);
        $request->expects($this->once())->method('getHeaderLine')->with('Content-Type')->willReturn($requestContentType);

        if ($useParsedBody) {
            $request->expects($this->once())->method('getParsedBody')->willReturn($expectedRequestContent);
        }

        $response->expects($this->once())->method('getStatusCode')->willReturn($code);
        $response->expects($this->once())->method('getHeaders')->willReturn($responseHeaders);
        $response->expects($this->once())->method('getBody')->willReturn($responseBody);
        $responseBody->expects($this->once())->method('__toString')->willReturn($responseContent);
        $response->expects($this->once())->method('getHeaderLine')->with('Content-Type')->willReturn($responseContentType);

        $result = new HttpRequestLog($request, $response);        

        $this->assertTrue(is_array($result->request));
        $this->assertTrue(is_array($result->response));
        $this->assertEquals(['uri', 'method', 'headers', 'body'], array_keys($result->request));
        $this->assertEquals(['status', 'headers', 'body'], array_keys($result->response));

        $this->assertSame($url, $result->request['uri']);
        $this->assertSame($method, $result->request['method']);
        $this->assertEquals($requestHeaders, $result->request['headers']);
        $this->assertEquals($expectedRequestContent, $result->request['body']);

        $this->assertSame($code, $result->response['status']);
        $this->assertEquals($responseHeaders, $result->response['headers']);
        $this->assertEquals($expectedResponseContent, $result->response['body']);
    }
}
