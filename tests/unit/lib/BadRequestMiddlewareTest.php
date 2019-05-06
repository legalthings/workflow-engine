<?php

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamInterface;
use Jasny\ValidationResult;
use Jasny\ValidationException;

/**
 * @covers BadRequestMiddleware
 */
class BadRequestMiddlewareTest extends \Codeception\Test\Unit
{
    /**
     * Provide data for testing 'invoke' method
     *
     * @return array
     */
    public function invokeProvider()
    {
        return [
            [AuthException::class, 401, 401],
            [AuthException::class, null, 403],
            [EntityNotFoundException::class, null, 404]
        ];
    }

    /**
     * Test '__invoke' method
     *
     * @dataProvider invokeProvider
     */
    public function testInvoke($exception, $code, $expectedCode)
    {
        $message = 'Foo error message';
        $next = function() use ($exception, $message, $code) {            
            $throw = isset($code) ?
                new $exception($message, $code) :
                new $exception($message);

            throw $throw;
        };

        $request = $this->createMock(ServerRequest::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(Response::class);
        $responseWithStatus = $this->createMock(Response::class);
        $responseWithHeader = $this->createMock(Response::class);
        $responseWithBody = $this->createMock(Response::class);

        $response->expects($this->once())->method('getBody')->willReturn($stream);
        $stream->expects($this->once())->method('write')->with($message);
        $response->expects($this->once())->method('withStatus')->with($expectedCode)->willReturn($responseWithStatus);
        $responseWithStatus->expects($this->once())->method('withHeader')
            ->with('Content-Type', 'text/plain')->willReturn($responseWithHeader);
        $responseWithHeader->expects($this->once())->method('withBody')
            ->with($stream)->willReturn($responseWithBody);

        $middleware = new BadRequestMiddleware();
        $result = $middleware($request, $response, $next);

        $this->assertSame($responseWithBody, $result);
    }

    /**
     * Test 'invoke' method for validation exception
     */
    public function testInvokeValidation()
    {
        $errors = ['Foo error message', 'And bar error'];
        $next = function() use ($errors) {            
            $validation = new ValidationResult();
            foreach ($errors as $error) {
                $validation->addError($error);
            }

            throw new ValidationException($validation);
        };

        $request = $this->createMock(ServerRequest::class);
        $stream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(Response::class);
        $responseWithStatus = $this->createMock(Response::class);
        $responseWithHeader = $this->createMock(Response::class);
        $responseWithBody = $this->createMock(Response::class);

        $response->expects($this->once())->method('getBody')->willReturn($stream);
        $stream->expects($this->once())->method('write')->with(json_encode($errors));
        $response->expects($this->once())->method('withStatus')->with(400)->willReturn($responseWithStatus);
        $responseWithStatus->expects($this->once())->method('withHeader')
            ->with('Content-Type', 'application/json')->willReturn($responseWithHeader);
        $responseWithHeader->expects($this->once())->method('withBody')
            ->with($stream)->willReturn($responseWithBody);

        $middleware = new BadRequestMiddleware();
        $result = $middleware($request, $response, $next);

        $this->assertSame($responseWithBody, $result);
    }

    /**
     * Test 'invoke' method, if exception is not catched
     *
     * @expectedException Exception
     * @expectedExceptionMessage foo
     */
    public function testInvokeNotCatch()
    {
        $request = $this->createMock(ServerRequest::class);
        $response = $this->createMock(Response::class);

        $next = function() {
            throw new Exception('foo');
        };

        $middleware = new BadRequestMiddleware();
        $middleware($request, $response, $next);
    }
}
