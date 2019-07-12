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

        $urlEncodedData = 'foo=bar&baz[]=1&baz[]=2';
        $expectedDecodedData = ['foo' => 'bar', 'baz' => [1, 2]];

        list($multipartWithHeaders, $multipartNoHeaders, $multipartInvalid) = $this->getMultipartData();
        $img = <<<IMAGE
?PNG

IHD?wS??iCCPICC Profilex?T?kA?6n??Zk?x?"IY?hE?6?bk
Y?<ߡ)??????9Nyx?+=?Y"|@5-?M?S?%?@?H8??qR>?׋??inf???O?????b??N?????~N??>?!?
??V?J?p?8?da?sZHO?Ln?}&???wVQ?y?g????E??0
 ??
   IDAc????????-IEND?B`?
IMAGE;

        $expectedMultipart = [
            'img' => [
                'value' => $img,
                'headers' => [
                    'content-disposition' => 'form-data; name="img"; filename="a.png"',
                    'content-type' => 'image/png'
                ]
            ],
            'foo' => [
                'value' => 'bar',
                'headers' => [
                    'content-disposition' => 'form-data; name="foo"'
                ]
            ],
            'rfc5987' => [
                'value' => 'rfc',
                'headers' => [
                    'content-disposition' => 'form-data; name="rfc5987"; text1*=iso-8859-1\'en\'%A3%20rates; text2*=UTF-8\'\'%c2%a3%20and%20%e2%82%ac%20rates'
                ]
            ]
        ];

        return [
            [
                'application/json', 
                $requestJson, 
                $requestContent, 
                'application/json', 
                $responseJson, 
                $responseContent
            ],
            [
                'foo;application/json; bar', 
                $requestJson, 
                $requestContent, 
                'foo;application/json; baz', 
                $responseJson, 
                $responseContent
            ],
            [
                'application/x-www-form-urlencoded', 
                $urlEncodedData, 
                $expectedDecodedData, 
                'application/json', 
                $responseJson, 
                $responseContent
            ],
            [
                'foo;application/x-www-form-urlencoded;bar', 
                $urlEncodedData, 
                $expectedDecodedData, 
                'application/json', 
                $responseJson, 
                $responseContent
            ],
            [
                'multipart/form-data', 
                $multipartWithHeaders, 
                $expectedMultipart, 
                'application/json', 
                $responseJson, 
                $responseContent
            ],
            [
                'multipart/form-data', 
                $multipartNoHeaders, 
                $expectedMultipart, 
                'application/json', 
                $responseJson, 
                $responseContent
            ],
            [
                'multipart/form-data', 
                $multipartInvalid, 
                $multipartInvalid, 
                'application/json', 
                $responseJson, 
                $responseContent
            ],
            [
                'foo;multipart/form-data;bar', 
                $multipartNoHeaders, 
                $expectedMultipart, 
                'application/json', 
                $responseJson, 
                $responseContent
            ],
            [
                'text/plain', 
                'Some content', 
                'Some content', 
                'text/plain', 
                'Another content', 
                'Another content'
            ]
        ];
    }

    /**
     * Get test multipart data
     *
     * @return array
     */
    protected function getMultipartData()
    {
        $multipartWithHeaders = <<<MULTI
User-Agent: curl/7.21.2 (x86_64-apple-darwin)
Host: localhost:8080
Accept: */*
Content-Length: 1143
Expect: 100-continue
X-Multi-Line: line one
    line two with space
    line three with tab
Content-Type: multipart/form-data; boundary=----------------------------83ff53821b7c

------------------------------83ff53821b7c
Content-Disposition: form-data; name="img"; filename="a.png"
Content-Type: image/png

?PNG

IHD?wS??iCCPICC Profilex?T?kA?6n??Zk?x?"IY?hE?6?bk
Y?<ߡ)??????9Nyx?+=?Y"|@5-?M?S?%?@?H8??qR>?׋??inf???O?????b??N?????~N??>?!?
??V?J?p?8?da?sZHO?Ln?}&???wVQ?y?g????E??0
 ??
   IDAc????????-IEND?B`?
------------------------------83ff53821b7c
Content-Disposition: form-data; name="foo"

bar
------------------------------83ff53821b7c
Content-Disposition: form-data; name="rfc5987"; text1*=iso-8859-1'en'%A3%20rates; text2*=UTF-8''%c2%a3%20and%20%e2%82%ac%20rates

rfc
------------------------------83ff53821b7c--
MULTI;

        $multipartNoHeaders = <<<MULTI
------------------------------83ff53821b7c
Content-Disposition: form-data; name="img"; filename="a.png"
Content-Type: image/png

?PNG

IHD?wS??iCCPICC Profilex?T?kA?6n??Zk?x?"IY?hE?6?bk
Y?<ߡ)??????9Nyx?+=?Y"|@5-?M?S?%?@?H8??qR>?׋??inf???O?????b??N?????~N??>?!?
??V?J?p?8?da?sZHO?Ln?}&???wVQ?y?g????E??0
 ??
   IDAc????????-IEND?B`?
------------------------------83ff53821b7c
Content-Disposition: form-data; name="foo"

bar
------------------------------83ff53821b7c
Content-Disposition: form-data; name="rfc5987"; text1*=iso-8859-1'en'%A3%20rates; text2*=UTF-8''%c2%a3%20and%20%e2%82%ac%20rates

rfc
------------------------------83ff53821b7c--
MULTI;

        $multipartInvalid = <<<MULTI
test
------------------------------83ff53821b7c
Content-Disposition: form-data; name="img"; filename="a.png"
Content-Type: image/png

?PNG

IHD?wS??iCCPICC Profilex?T?kA?6n??Zk?x?"IY?hE?6?bk
Y?<ߡ)??????9Nyx?+=?Y"|@5-?M?S?%?@?H8??qR>?׋??inf???O?????b??N?????~N??>?!?
??V?J?p?8?da?sZHO?Ln?}&???wVQ?y?g????E??0
 ??
   IDAc????????-IEND?B`?
------------------------------83ff53821b7c
Content-Disposition: form-data; name="foo"

bar
------------------------------83ff53821b7c
Content-Disposition: form-data; name="rfc5987"; text1*=iso-8859-1'en'%A3%20rates; text2*=UTF-8''%c2%a3%20and%20%e2%82%ac%20rates

rfc
------------------------------83ff53821b7c--
MULTI;

        return [$multipartWithHeaders, $multipartNoHeaders, $multipartInvalid];
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
        $expectedResponseContent
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
