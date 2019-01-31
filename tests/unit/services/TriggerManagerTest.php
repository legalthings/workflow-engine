<?php

use Jasny\EventDispatcher\EventDispatcher;
use Jasny\ValidationException;
use Jasny\ValidationResult;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers TriggerManager
 */
class TriggerManagerTest extends \Codeception\Test\Unit
{
    /**
     * Create mock for a trigger handler.
     *
     * @param bool          $expectInvoked
     * @param Process|null  $process
     * @param Action|null   $action
     * @param mixed         $return
     * @return MockObject
     */
    protected function createHandlerMock(bool $expectInvoked = false, $process = null, $action = null, $return = null)
    {
        $callback = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->setMockClassName(ucfirst($key ?? 'Always') . 'MockTriggerHandler')
            ->getMock();

        if (!$expectInvoked) {
            $callback->expects($this->never())->method('__invoke');
            return $callback;
        }

        $actor = $process->getActor('client');

        $invoke = $callback->expects($this->once())->method('__invoke');

        $invoke->with(
            $this->identicalTo($process),
            $this->callback(function ($arg) use ($action, $actor) {
                $this->assertInstanceOf(Action::class, $arg);
                $this->assertAttributeEquals($action->key, 'key', $arg);
                $this->assertAttributeEquals($action->title, 'title', $arg);
                $this->assertAttributeEquals($actor, 'actor', $arg);

                return true;
            }));

        if ($return === $action) {
            $invoke->willReturnArgument(1); // The action is cloned and modified.
        } else {
            $invoke->willReturn($return);
        }

        return $callback;
    }

    /**
     * @return Process&MockObject
     */
    protected function createProcess(): Process
    {
        $process = new Process();
        $process->id = '00000000-0000-0000-0000-000000000000';

        $process->actors['manager'] = Actor::fromData(['title' => 'Manager']);
        $process->actors['client'] = Actor::fromData(['title' => 'Client']);

        $process->current = new CurrentState();
        $process->current->key = ':initial';

        $process->current->actions['foo'] = Action::fromData([
            'schema' => 'https://example.com/foo',
            'title' => 'Foo',
            'actors' => ['client'],
        ]);

        $process->current->actions['qux'] = Action::fromData([
            'schema' => 'https://example.com/qux',
            'title' => 'Qux',
            'actors' => ['client'],
        ]);

        $process->current->actions['queue'] = Action::fromData([
            'schema' => 'https://example.com/queue',
            'title' => 'Queue',
            'actors' => ['client'],
        ]);

        return $process;
    }


    public function provider()
    {
        return [
            [null],
            ['foo'],
            ['queue'],
            ['qux'],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function test(?string $actionKey)
    {
        $trigger = new TriggerManager();
        $dispatcher = new EventDispatcher();

        $process = $this->createProcess();
        $action = $process->current->actions[$actionKey ?? 'foo'];

        $alwaysHandler = $this->createHandlerMock(1, $process, $action, $action);
        $trigger->add($dispatcher, null, $alwaysHandler);

        $fooResponse = new Response();
        $fooHandler = $this->createHandlerMock($action->key === 'foo', $process, $action, $fooResponse);
        $trigger->add($dispatcher, 'https://example.com/foo', $fooHandler);

        $barHandler = $this->createHandlerMock();
        $trigger->add($dispatcher, 'https://example.com/bar', $barHandler);

        $queueHandler = $this->createHandlerMock($action->key === 'queue', $process, $action, null);
        $trigger->add($dispatcher, 'https://example.com/queue', $queueHandler);

        $defaultResponse = new Response();
        $defaultHandler = $this->createHandlerMock($action->key === 'qux', $process, $action, $defaultResponse);
        $trigger->add($dispatcher, null, $defaultHandler);

        $process->setDispatcher($dispatcher);

        $response = $trigger->invoke($process, $actionKey, 'client');

        $expected =
            ($action->key === 'foo' ? $fooResponse : null) ??
            ($action->key === 'qux' ? $defaultResponse : null);

        $this->assertSame($expected, $response);
    }

    /**
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Action 'other' is not allowed in state ':initial' of process '00000000-0000-0000-0000-000000000000'
     */
    public function testActionNotAllowedInState()
    {
        $trigger = new TriggerManager();
        $dispatcher = new EventDispatcher();

        $process = $this->createProcess();

        $handler = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $handler->expects($this->never())->method('__invoke');
        $trigger->add($dispatcher, null, $handler);

        $process->setDispatcher($dispatcher);

        $response = $trigger->invoke($process, 'other', 'client');

        $this->assertNull($response);
    }

    /**
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Manager is not allowed to perform action 'foo' in process '00000000-0000-0000-0000-000000000000'
     */
    public function testActionNotAllowedByActor()
    {
        $trigger = new TriggerManager();
        $dispatcher = new EventDispatcher();

        $process = $this->createProcess();

        $handler = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $handler->expects($this->never())->method('__invoke');
        $trigger->add($dispatcher, null, $handler);

        $process->setDispatcher($dispatcher);

        $response = $trigger->invoke($process, 'foo', 'manager');

        $this->assertNull($response);
    }

    public function testDefaultActionNotAllowedByActor()
    {
        $trigger = new TriggerManager();
        $dispatcher = new EventDispatcher();

        $process = $this->createProcess();

        $handler = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $handler->expects($this->never())->method('__invoke');
        $trigger->add($dispatcher, null, $handler);

        $process->setDispatcher($dispatcher);

        $response = $trigger->invoke($process, null, 'manager');

        $this->assertNull($response);
    }

    public function testErrorResponse()
    {
        $trigger = new TriggerManager();
        $dispatcher = new EventDispatcher();

        $process = $this->createProcess();

        $handler = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $handler->expects($this->once())->method('__invoke')->willThrowException(new RuntimeException('Some error'));
        $trigger->add($dispatcher, null, $handler);

        $process->setDispatcher($dispatcher);

        $response = $trigger->invoke($process, 'foo', 'client');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertAttributeEquals('error', 'key', $response);
        $this->assertAttributeEquals('An error occured', 'title', $response);
        $this->assertAttributeEquals((object)['message' => 'Some error'], 'data', $response);

        $this->assertAttributeInstanceOf(Action::class, 'action', $response);
        $this->assertAttributeEquals('foo', 'key', $response->action);
        $this->assertAttributeEquals('Foo', 'title', $response->action);
        $this->assertAttributeEquals($process->getActor('client'), 'actor', $response->action);
    }

    public function testValidationErrorResponse()
    {
        $trigger = new TriggerManager();
        $dispatcher = new EventDispatcher();

        $process = $this->createProcess();

        $handler = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $handler->expects($this->once())->method('__invoke')
            ->willThrowException(new ValidationException(ValidationResult::error('Some error')));
        $trigger->add($dispatcher, null, $handler);

        $process->setDispatcher($dispatcher);

        $response = $trigger->invoke($process, 'foo', 'client');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertAttributeEquals('error', 'key', $response);
        $this->assertAttributeEquals('An error occured', 'title', $response);
        $this->assertAttributeEquals((object)[
            'message' => 'Validation failed',
            'errors' => ['Some error'],
        ], 'data', $response);

        $this->assertAttributeInstanceOf(Action::class, 'action', $response);
        $this->assertAttributeEquals('foo', 'key', $response->action);
        $this->assertAttributeEquals('Foo', 'title', $response->action);
        $this->assertAttributeEquals($process->getActor('client'), 'actor', $response->action);
    }
}
