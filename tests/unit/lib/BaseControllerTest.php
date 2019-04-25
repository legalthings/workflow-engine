<?php

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamInterface;
use Jasny\ValidationResult;
use Jasny\ValidationException;

/**
 * @covers BaseController
 */
class BaseControllerTest extends \Codeception\Test\Unit
{
    use Jasny\TestHelper;

    /**
     * Provide data for testing 'output' method, when using json view
     *
     * @return array
     */
    public function outputJsonViewProvider()
    {
        $scenario = $this->createMock(Scenario::class);
        $process = $this->createMock(Process::class);

        return [
            [$scenario, true, 'pretty.scenario'],
            [$scenario, 'foo', 'pretty.scenario'],
            [$scenario, false, null],
            [$scenario, null, null],
            [$scenario, '', null],
            [$process, true, 'pretty.process'],
            [$process, 'foo', 'pretty.process'],
            [$process, false, null],
            [$process, null, null],
            [$process, '', null],
            [['foo' => 'bar'], true, null],
        ];
    }

    /**
     * Test 'output' method, when using json view
     *
     * @dataProvider outputJsonViewProvider
     */
    public function testOutputJsonView($data, $pretty, $withDecorator)
    {
        $jsonView = $this->createMock(JsonView::class);
        $jsonViewInited = $this->createMock(JsonView::class);
        $request = $this->createMock(ServerRequest::class);
        $response = $this->createMock(Response::class);
        $responseReady = $this->createMock(Response::class);

        $mockMethods = ['getRequest', 'getResponse', 'setResponse'];
        $contoller = $this->getMockForAbstractClass(BaseController::class, [], '', false, true, true, $mockMethods);
        $this->setPrivateProperty($contoller, 'jsonView', $jsonView);

        $contoller->expects($this->once())->method('getRequest')->willReturn($request);
        $request->expects($this->once())->method('getAttribute')->with('pretty-json')->willReturn($pretty);

        if (isset($withDecorator)) {
            $jsonView->expects($this->once())->method('withDecorator')->with($withDecorator)->willReturn($jsonViewInited);
        } else {
            $jsonView->expects($this->never())->method('withDecorator');
            $jsonViewInited = $jsonView;
        }

        $contoller->expects($this->once())->method('getResponse')->willReturn($response);
        $jsonViewInited->expects($this->once())->method('output')
            ->with($this->identicalTo($response), $data)->willReturn($responseReady);
        $contoller->expects($this->once())->method('setResponse')->with($responseReady);

        $contoller->output($data, 'json');
    }

    /**
     * Provide data for testing 'output' method, if parent method is called
     *
     * @return array
     */
    public function outputParentProvider()
    {
        return [
            ['json', null],
            ['html', $this->createMock(JsonView::class)],
        ];
    }

    /**
     * Test 'output' method, if parent method is called
     *
     * @dataProvider outputParentProvider
     */
    public function testOutputParent($format, $jsonView)
    {
        $process = $this->createMock(Process::class);
        $response = $this->createMock(Response::class);
        $stream = $this->createMock(StreamInterface::class);

        $mockMethods = ['outputContentType', 'serializeData', 'getResponse'];
        $contoller = $this->getMockForAbstractClass(BaseController::class, [], '', false, true, true, $mockMethods);

        if (isset($jsonView)) {
            $this->setPrivateProperty($contoller, 'jsonView', $jsonView);
        }

        $contoller->expects($this->once())->method('outputContentType')->with($format);
        $contoller->expects($this->once())->method('getResponse')->willReturn($response);
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $contoller->output($process, $format);
    }
}
