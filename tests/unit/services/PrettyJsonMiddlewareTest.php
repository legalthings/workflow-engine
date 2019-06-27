<?php

use Improved as i;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * @covers PrettyJsonMiddleware
 */
class PrettyJsonMiddlewareTest extends \Codeception\Test\Unit
{
    /**
     * Provide data for testing '__invoke' method
     *
     * @return array
     */
    public function invokeProvider()
    {
        return [
            ['application/json;view=complete', false],
            ['application/json;view=complete, foo/bar', false],
            ['application/json;view=complete, foo/bar;view=pretty', false],
            ['application/json;view=pretty', true],
            ['application/json;view=pretty, foo/bar', true],
            ['application/json;view=pretty, foo/bar;view=complete', true],
            ['application/json', true]
        ];
    }

    /**
     * Test '__invoke' method
     *
     * @dataProvider invokeProvider
     */
    public function testInvoke($accept, $expected)
    {
        $request = $this->createMock(ServerRequest::class);
        $response = $this->createMock(Response::class);

        $request->expects($this->once())->method('getHeaderLine')->with('Accept')->willReturn($accept);
        $request->expects($this->once())->method('withAttribute')->with('pretty-json', $expected)->willReturn($request);

        $middleware = new PrettyJsonMiddleware();
        $callback = function(ServerRequest $request, Response $response) {
            $response->nextProcessed = true;
            return $response;
        };

        $result = $middleware($request, $response, $callback);

        $this->assertSame($response, $result);
        $this->assertTrue($result->nextProcessed);
    }
}
