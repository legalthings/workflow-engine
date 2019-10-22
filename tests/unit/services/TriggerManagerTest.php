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
                $this->assertEquals($action->key, $arg->key);
                $this->assertEquals($action->title, $arg->title);
                $this->assertEquals($actor, $arg->actor);

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
        $process->current->key = 'initial';

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
        $manager = new TriggerManager();

        $process = $this->createProcess();
        $action = $process->current->actions[$actionKey ?? 'foo'];

        $alwaysHandler = $this->createHandlerMock(1, $process, $action, $action);
        $manager = $manager->with(null, $alwaysHandler);

        $fooResponse = new Response();
        $fooHandler = $this->createHandlerMock($action->key === 'foo', $process, $action, $fooResponse);
        $manager = $manager->with('https://example.com/foo', $fooHandler);

        $barHandler = $this->createHandlerMock();
        $manager = $manager->with('https://example.com/bar', $barHandler);

        $queueHandler = $this->createHandlerMock($action->key === 'queue', $process, $action, null);
        $manager = $manager->with('https://example.com/queue', $queueHandler);

        $defaultResponse = new Response();
        $defaultHandler = $this->createHandlerMock($action->key === 'qux', $process, $action, $defaultResponse);
        $manager = $manager->with(null, $defaultHandler);

        $response = $manager->invoke($process, $actionKey, 'client');

        $expected =
            ($action->key === 'foo' ? $fooResponse : null) ??
            ($action->key === 'qux' ? $defaultResponse : null);

        $this->assertSame($expected, $response);

        if ($response !== null) {
            $this->assertEquals($action, $response->action);
            $this->assertNotSame($action, $response->action);
            $this->assertEquals($process->actors['client'], $response->actor);
            $this->assertNotSame($process->actors['client'], $response->actor);
        }
    }

    /**
     * Provide data for testing 'invoke' method, in case when actor is not found
     *
     * @return array
     */
    public function actorNotFoundProvider()
    {
        return [
            ['non_exist_actor'],
            [(new Actor())->set('key', 'non_exist_actor')]
        ];
    }

    /**
     * Test 'invoke' method, in case when actor is not found
     *
     * @dataProvider actorNotFoundProvider
     * @expectedException Jasny\ValidationException
     * @expectedExceptionMessage Unknown actor 'non_exist_actor'
     */
    public function testActorNotFound($actor)
    {
        $manager = new TriggerManager();
        $process = $this->createProcess();

        $handler = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $handler->expects($this->never())->method('__invoke');
        $manager = $manager->with(null, $handler);

        $manager->invoke($process, 'qux', $actor);
    }

    /**
     * @expectedException \Jasny\ValidationException
     * @expectedExceptionMessage Action 'other' is not allowed in state 'initial'
     */
    public function testActionNotAllowedInState()
    {
        $manager = new TriggerManager();
        $process = $this->createProcess();

        $handler = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $handler->expects($this->never())->method('__invoke');
        $manager = $manager->with(null, $handler);

        $response = $manager->invoke($process, 'other', 'client');

        $this->assertNull($response);
    }

    /**
     * @expectedException \Jasny\ValidationException
     * @expectedExceptionMessage Manager is not allowed to perform action 'foo'
     */
    public function testActionNotAllowedByActor()
    {
        $manager = new TriggerManager();
        $process = $this->createProcess();

        $handler = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $handler->expects($this->never())->method('__invoke');
        $manager = $manager->with(null, $handler);

        $response = $manager->invoke($process, 'foo', 'manager');

        $this->assertNull($response);
    }

    public function testDefaultActionNotAllowedByActor()
    {
        $manager = new TriggerManager();
        $process = $this->createProcess();

        $handler = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $handler->expects($this->never())->method('__invoke');
        $manager = $manager->with(null, $handler);

        $response = $manager->invoke($process, null, 'manager');

        $this->assertNull($response);
    }

    public function testErrorResponse()
    {
        $manager = new TriggerManager();
        $process = $this->createProcess();

        $handler = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $handler->expects($this->once())->method('__invoke')->willThrowException(new RuntimeException('Some error'));
        $manager = $manager->with(null, $handler);

        $response = $manager->invoke($process, 'foo', 'client');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('error', $response->key);
        $this->assertEquals('An error occured', $response->title);
        $this->assertEquals((object)['message' => 'Some error'], $response->data);

        $this->assertInstanceOf(Action::class, $response->action);
        $this->assertEquals('foo', $response->action->key);
        $this->assertEquals('Foo', $response->action->title);
        $this->assertEquals($process->getActor('client'), $response->actor);
    }

    public function testValidationErrorResponse()
    {
        $manager = new TriggerManager();
        $process = $this->createProcess();

        $handler = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $handler->expects($this->once())->method('__invoke')
            ->willThrowException(new ValidationException(ValidationResult::error('Some error')));
        $manager = $manager->with(null, $handler);

        $response = $manager->invoke($process, 'foo', 'client');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('error', $response->key);
        $this->assertEquals('An error occured', $response->title);
        $this->assertAttributeEquals((object)[
            'message' => "Validation failed",
            'errors' => ['Some error'],
        ], 'data', $response);

        $this->assertInstanceOf(Action::class, $response->action);
        $this->assertEquals('foo', $response->action->key);
        $this->assertEquals('Foo', $response->action->title);
        $this->assertEquals($process->getActor('client'), $response->actor);
    }

    public function testDispatcher()
    {
        $manager = new TriggerManager();
        $process = $this->createProcess();

        $handlerResponse = new Response();
        $dispatcherResponse = new Response();

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects($this->once())->method('trigger')
            ->with('trigger', $this->identicalTo($process), $this->identicalTo($handlerResponse))
            ->willReturn($dispatcherResponse);

        $process->setDispatcher($dispatcher);

        $action = $process->current->actions['foo'];

        $handler = $this->createHandlerMock(true, $process, $action, $handlerResponse);
        $manager = $manager->with(null, $handler);

        $response = $manager->invoke($process, 'foo', 'client');

        $this->assertSame($dispatcherResponse, $response);
    }
}
