<?php

use Jasny\EventDispatcher\EventDispatcher;
use Jasny\DB\EntitySet;
use Jasny\ValidationResult;

/**
 * @covers Process
 */
class ProcessTest extends \Codeception\Test\Unit
{
    use Jasny\TestHelper;

    /**
     * @var Process
     **/
    protected $process;

    /**
     * Do actions before each test case
     */
    public function _before()
    {
        $this->process = new Process();
    }

    /**
     * Test '__construct' method
     */
    public function testConstruct()
    {
        $idRegexp = '/[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-(8|9|a|b)[a-f0-9]{3}-[a-f0-9]{12}/';

        $this->assertAttributeInstanceOf(EventDispatcher::class, 'dispatcher', $this->process);
        $this->assertTrue((bool)preg_match($idRegexp, $this->process->id));
    }

    /**
     * Test 'setDispatcher' method
     */
    public function testSetDispatcher()
    {
        $dispatcher = $this->createMock(EventDispatcher::class);
        $this->process->setDispatcher($dispatcher);

        $this->assertAttributeSame($dispatcher, 'dispatcher', $this->process);
    }

    /**
     * Test 'dispatch' method
     */
    public function testDispatch()
    {
        $event = 'foo_event';
        $payload = ['foo' => 'bar'];
        $expected = 'foo_result';

        $dispatcher = $this->createMock(EventDispatcher::class);
        $this->setPrivateProperty($this->process, 'dispatcher', $dispatcher);

        $dispatcher->expects($this->once())->method('trigger')->with(
            $this->identicalTo($event), 
            $this->identicalTo($this->process), 
            $payload
        )->willReturn($expected);

        $result = $this->process->dispatch($event, $payload);

        $this->assertSame($expected, $result);
    }

    /**
     * Provide data for testing 'cast' method for 'next' property
     *
     * @return array
     */
    public function castNextProvider()
    {
        $items = [
            $this->createMock(NextState::class),
            $this->createMock(NextState::class)
        ];

        $set = EntitySet::forClass(NextState::class, $items, 0, EntitySet::ALLOW_DUPLICATES);

        return [
            [$items, $set],
            [null, null]
        ];
    }

    /**
     * Test 'cast' method for 'next' property
     *
     * @dataProvider castNextProvider
     */
    public function testCastNext($next, $expected)
    {
        $this->process->next = $next;
        $result = $this->process->cast();

        $this->assertSame($this->process, $result);
        $this->assertEquals($expected, $this->process->next);
    }

    /**
     * Test 'cast' method for 'previous' property
     */
    public function testCastPrevious()
    {
        $previous = [
            $this->createMock(Response::class),
            $this->createMock(Response::class)
        ];

        $expected = EntitySet::forClass(Response::class, $previous, 0, EntitySet::ALLOW_DUPLICATES);

        $this->process->previous = $previous;
        $result = $this->process->cast();

        $this->assertSame($this->process, $result);
        $this->assertEquals($expected, $this->process->previous);
    }

    /**
     * Provide data for testing 'cast' method for 'chain' property
     *
     * @return array
     */
    public function castChainProvider()
    {
        return [
            ['foo', 'foo'],
            [null, null],
            [['id' => 'foo'], 'foo'],
            [(object)['id' => 'foo'], 'foo'],
        ];
    }

    /**
     * Test 'cast' method for 'chain' property
     *
     * @dataProvider castChainProvider
     */
    public function testCastChain($chain, $expected)
    {
        $this->process->chain = $chain;
        $result = $this->process->cast();

        $this->assertSame($this->process, $result);
        $this->assertEquals($expected, $this->process->chain);
    }

    /**
     * Test 'cast' method with calling dispatcher
     */
    public function testCastCallDispatcher()
    {
        $dispatcher = $this->createMock(EventDispatcher::class);        
        $this->setPrivateProperty($this->process, 'dispatcher', $dispatcher);

        $dispatcher->expects($this->once())->method('trigger')->with('cast', $this->process);
        
        $result = $this->process->cast();

        $this->assertSame($this->process, $result);
    }

    /**
     * Provide data for testing 'setValues' method
     *
     * @return array
     */
    public function setValuesActorsProvider()
    {
        $setActors = $this->getNewActorsData();
        $setAsObjects = $setActors;

        foreach ($setAsObjects as $key => $data) {
            if (is_array($data)) {
                $setAsObjects[$key] = (object)$data;
            }
        }

        return [
            [$setActors],
            [$setAsObjects]
        ];
    }

    /**
     * Test 'setValues' method
     *
     * @dataProvider setValuesActorsProvider
     */
    public function testSetValuesActors($setActors)
    {
        $oldActors = $this->getOldActors();
        $this->process->actors = AssocEntitySet::forClass(Actor::class, $oldActors);        

        $values = ['actors' => $setActors];
        $this->process->setValues($values);

        $actors = $this->process->actors->getArrayCopy();

        $this->assertCount(3, $actors);

        $this->assertSame('foo', $actors['foo']->key);
        $this->assertSame('Changed foo actor', $actors['foo']->title);
        $this->assertSame('foo_changed', $actors['foo']->identity->id);
        $this->assertInstanceOf(Identity::class, $actors['foo']->identity);
        
        $this->assertSame('bar', $actors['bar']->key);
        $this->assertSame('New bar', $actors['bar']->title);
        $this->assertSame('bar_identity_id', $actors['bar']->identity->id);
        $this->assertInstanceOf(Identity::class, $actors['bar']->identity);
        
        $this->assertSame('baz', $actors['baz']->key);
        $this->assertSame('Baz actor', $actors['baz']->title);
        $this->assertSame('new_identity_id', $actors['baz']->identity->id);
        $this->assertInstanceOf(Identity::class, $actors['baz']->identity);
    }

    /**
     * Test 'setValues' method, if actors can be added
     */
    public function testSetValuesAddNewActors()
    {
        $this->process->actors = $this->getOldActors(); // so actors are not assoc entity set
        $setActors = $this->getNewActorsData();

        $values = ['actors' => $setActors];
        $this->process->setValues($values);

        $this->assertInstanceOf(AssocEntitySet::class, $this->process->actors);
        $actors = $this->process->actors->getArrayCopy();

        $this->assertCount(5, $actors);

        $this->assertSame('zoos', $actors['zoos']->key);
        $this->assertSame('Zoos actor', $actors['zoos']->title);
        $this->assertSame('zoos_identity_id', $actors['zoos']->identity->id);
        $this->assertInstanceOf(Identity::class, $actors['zoos']->identity);

        $this->assertSame('bob', $actors['bob']->key);
        $this->assertSame('Some Bob', $actors['bob']->title);
        $this->assertSame('bobs', $actors['bob']->identity->id);
        $this->assertInstanceOf(Identity::class, $actors['bob']->identity);

        $this->assertSame('foo', $actors['foo']->key);
        $this->assertSame('Changed foo actor', $actors['foo']->title);
        $this->assertSame('foo_changed', $actors['foo']->identity->id);
        $this->assertInstanceOf(Identity::class, $actors['foo']->identity);
        
        $this->assertSame('bar', $actors['bar']->key);
        $this->assertSame('New bar', $actors['bar']->title);
        $this->assertSame(null, $actors['bar']->identity);
        
        $this->assertSame('baz', $actors['baz']->key);
        $this->assertSame(null, $actors['baz']->title);
        $this->assertSame(null, $actors['baz']->identity);
    }    

    /**
     * Get old values for process actors
     *
     * @return array
     */
    protected function getOldActors()
    {
        return [
            (new Actor)->setValues([
                'key' => 'foo',
                'title' => 'Foo actor',
                'identity' => 'foo_identity_id'
            ]),
            (new Actor)->setValues([
                'key' => 'bar',
                'title' => 'Bar actor',
                'identity' => 'bar_identity_id'
            ]),
            (new Actor)->setValues([
                'key' => 'baz',
                'title' => 'Baz actor',
                'identity' => 'baz_identity_id'
            ]),
        ];
    }

    /**
     * Get new values for setting actors
     *
     * @return array
     */
    protected function getNewActorsData()
    {
        return [
            [
                'key' => 'zoos',
                'title' => 'Zoos actor',
                'identity' => 'zoos_identity_id'
            ],
            'bob' => [
                'title' => 'Some Bob',
                'identity' => 'bobs'
            ],
            [
                'key' => 'foo',
                'title' => 'Changed foo actor',
                'identity' => 'foo_changed'
            ],
            'bar' => [
                'title' => 'New bar'
            ],
            'baz' => 'new_identity_id'
        ];
    }

    /**
     * Provide data for testing 'hasActor' method
     *
     * @return array
     */
    public function hasActorProvider()
    {
        $find1 = $this->createMock(Actor::class);
        $find2 = 'foo';

        return [
            [false, $find1, true],
            [false, $find2, true],
            [true, $find1, true],
            [true, $find2, true],
            [false, $find1, false],
            [false, $find2, false],
            [true, $find1, false],
            [true, $find2, false],
            [true, null, false],
            [true, false, false],
            [true, 12, false],
        ];
    }

    /**
     * Test 'hasActor' method
     *
     * @dataProvider hasActorProvider
     */
    public function testHasActor($isEntitySet, $findActor, $expected)
    {
        $actors = [
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class)
        ];

        if ($isEntitySet) {
            $actors = AssocEntitySet::forClass(Actor::class, $actors);        
        }

        $findCallback = $this->callback($this->getFindActorCallback($findActor));

        $actors[1]->expects($this->any())->method('matches')->with($findCallback)->willReturn($expected);

        $actors[0]->expects($this->any())->method('matches')->with($findCallback)->willReturn(false);
        $actors[2]->expects($this->any())->method('matches')->with($findCallback)->willReturn(false);

        $this->process->actors = $actors;

        $result = $this->process->hasActor($findActor);

        $this->assertSame($expected, $result);
    }

    /**
     * Test 'getActor' method
     *
     * @dataProvider hasActorProvider
     */
    public function testGetActor($isEntitySet, $findActor, $expected)
    {
        $actors = [
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class)
        ];

        if ($isEntitySet) {
            $actors = AssocEntitySet::forClass(Actor::class, $actors);        
        }

        $findCallback = $this->callback($this->getFindActorCallback($findActor));

        $actors[1]->expects($this->any())->method('matches')->with($findCallback)->willReturn($expected);
        $actors[3]->expects($this->any())->method('matches')->with($findCallback)->willReturn($expected);

        $actors[0]->expects($this->any())->method('matches')->with($findCallback)->willReturn(false);
        $actors[2]->expects($this->any())->method('matches')->with($findCallback)->willReturn(false);

        $this->process->actors = $actors;

        if (!$expected) {
            $this->expectException(RangeException::class);
            $this->expectExceptionMessage('Unable to get first element; iterable is empty');
        }

        $result = $this->process->getActor($findActor);

        if ($expected){
            $this->assertSame($actors[1], $result);
        }
    }

    /**
     * Get callback to test find actor argument
     *
     * @param mixed $findActor 
     * @return callable
     */
    protected function getFindActorCallback($findActor)
    {
        return function($find) use ($findActor) {
            if ($findActor instanceof Actor) {
                return $find === $findActor;
            }
            
            $check = isset($findActor) ? (string)$findActor : null;

            return $find instanceof Actor && $find->key === $check;
        };
    }

    /**
     * Provide data for testing 'hasKnownActors' method
     *
     * @return array
     */
    public function hasKnownActorsProvider()
    {
        $identity = $this->createMock(Identity::class);

        return [
            [false, $identity, true],
            [false, 'foo', true],
            [true, $identity, true],
            [true, 'foo', true],
            [false, null, false],
            [true, null, false],
        ];
    }

    /**
     * Test 'hasKnownActors' method
     *
     * @dataProvider hasKnownActorsProvider
     */
    public function testHasKnownActors($isEntitySet, $identity, $expected)
    {
        $actors = [
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class)
        ];

        $actors[1]->identity = $identity;

        if ($isEntitySet) {
            $actors = AssocEntitySet::forClass(Actor::class, $actors);        
        }

        $this->process->actors = $actors;

        $result = $this->process->hasKnownActors();

        $this->assertSame($expected, $result);
    }

    /**
     * Provide data for testing 'getActorForAction' method
     *
     * @return array
     */
    public function getActorForActionProvider()
    {
        return [
            [$this->createMock(Actor::class)],
            ['foo']
        ];
    }

    /**
     * Test 'getActorForAction' method
     *
     * @dataProvider getActorForActionProvider
     */
    public function testGetActorForAction($findActor)
    {
        $actionKey = 'foo';
        $action = $this->createMock(Action::class);

        $actors = [
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class)
        ];

        $findCallback = $this->callback($this->getFindActorCallback($findActor));

        $actors[1]->expects($this->any())->method('matches')->with($findCallback)->willReturn(true);
        $actors[3]->expects($this->any())->method('matches')->with($findCallback)->willReturn(true);
        $actors[4]->expects($this->any())->method('matches')->with($findCallback)->willReturn(true);

        $actors[0]->expects($this->any())->method('matches')->with($findCallback)->willReturn(false);
        $actors[2]->expects($this->any())->method('matches')->with($findCallback)->willReturn(false);

        $action->expects($this->exactly(2))->method('isAllowedBy')->withConsecutive(
            [$this->identicalTo($actors[1])],
            [$this->identicalTo($actors[3])]
        )->willReturnOnConsecutiveCalls(false, true);

        $process = $this->createPartialMock(Process::class, ['getAvailableAction']);
        $process->expects($this->once())->method('getAvailableAction')->with($actionKey)->willReturn($action);

        $process->actors = $actors;        
        $result = $process->getActorForAction($actionKey, $findActor);

        $this->assertSame($actors[3], $result);
    }

    /**
     * Test 'getActorForAction' method, if no matching actors found
     *
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Actor not found
     */
    public function testGetActorForActionNoMatch()
    {
        $actionKey = 'foo';
        $action = $this->createMock(Action::class);
        $findActor = $this->createMock(Actor::class);

        $actors = [
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class)
        ];

        $findCallback = $this->callback($this->getFindActorCallback($findActor));

        $actors[0]->expects($this->any())->method('matches')->with($findCallback)->willReturn(false);
        $actors[1]->expects($this->any())->method('matches')->with($findCallback)->willReturn(false);
        $actors[2]->expects($this->any())->method('matches')->with($findCallback)->willReturn(false);

        $action->expects($this->never())->method('isAllowedBy');

        $process = $this->createPartialMock(Process::class, ['getAvailableAction']);
        $process->expects($this->once())->method('getAvailableAction')->with($actionKey)->willReturn($action);

        $process->actors = $actors;        

        $process->getActorForAction($actionKey, $findActor);
    }

    /**
     * Test 'getActorForAction' method, if action is not allowed for found actors
     */
    public function testGetActorForActionNotAllowed()
    {
        $actionKey = 'foo';
        $action = $this->createMock(Action::class);
        $findActor = $this->createMock(Actor::class);

        $actors = [
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class)
        ];

        $findCallback = $this->callback($this->getFindActorCallback($findActor));

        $actors[1]->expects($this->any())->method('matches')->with($findCallback)->willReturn(true);
        $actors[3]->expects($this->any())->method('matches')->with($findCallback)->willReturn(true);

        $actors[0]->expects($this->any())->method('matches')->with($findCallback)->willReturn(false);
        $actors[2]->expects($this->any())->method('matches')->with($findCallback)->willReturn(false);

        $action->expects($this->exactly(2))->method('isAllowedBy')->withConsecutive(
            [$this->identicalTo($actors[1])],
            [$this->identicalTo($actors[3])]
        )->willReturnOnConsecutiveCalls(false, false);

        $process = $this->createPartialMock(Process::class, ['getAvailableAction']);
        $process->expects($this->once())->method('getAvailableAction')->with($actionKey)->willReturn($action);

        $process->actors = $actors;        
        $result = $process->getActorForAction($actionKey, $findActor);

        $this->assertSame(null, $result);
    }

    /**
     * Test 'getAvailableAction' method
     */
    public function testGetAvailableAction()
    {
        $expected = $this->createMock(Action::class);

        $this->process->current = (object)[
            'actions' => [
                'foo' => $this->createMock(Action::class),
                'bar' => $expected,
                'baz' => $this->createMock(Action::class)
            ]
        ];

        $result = $this->process->getAvailableAction('bar');

        $this->assertSame($expected, $result);
    }

    /**
     * Test 'getAvailableAction' method, if action is not found
     *
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Action 'bar' is not available in state 'test_action' for process 'foo_id'
     */
    public function testGetAvailableActionNotFound()
    {
        $this->process->id = 'foo_id';
        $this->process->current = (object)[
            'key' => 'test_action',
            'actions' => [
                'foo' => $this->createMock(Action::class),
                'baz' => $this->createMock(Action::class)
            ]
        ];

        $this->process->getAvailableAction('bar');
    }

    /**
     * Provide data for testing 'isFinished' method
     *
     * @return array
     */
    public function isFinishedProvider()
    {
        return [
            ['foo', false],
            ['success', false],
            ['failed', false],
            ['cancelled', false],
            ['error', false],
            [':error', false],
            [':success', true],
            [':failed', true],
            [':cancelled', true]
        ];
    }

    /**
     * Test 'isFinished' method
     *
     * @dataProvider isFinishedProvider
     */
    public function testIsFinished($key, $expected)
    {
        $this->process->current = (object)['key' => $key];

        $result = $this->process->isFinished();

        $this->assertSame($expected, $result);
    }

    /**
     * Provide data for testing 'validate' method
     *
     * @return array
     */
    public function validateProvider()
    {
        return [
            [true, ['Event dispatcher error']],
            [false, ['no known actors', 'Event dispatcher error']]
        ];
    }

    /**
     * Test 'validate' method
     *
     * @dataProvider validateProvider
     */
    public function testValidate($hasKnownActors, $expected)
    {
        $process = $this->createPartialMock(Process::class, ['hasKnownActors']);
        $process->expects($this->once())->method('hasKnownActors')->willReturn($hasKnownActors);

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects($this->once())->method('trigger')->with(
            'validate', 
            $this->identicalTo($process),
            $this->callback(function($param) {
                return $param instanceof ValidationResult;
            })
        )->will($this->returnCallback(function($event, $process, $validation) {
            $validation->addError('Event dispatcher error');

            return $validation;
        }));

        $this->setPrivateProperty($process, 'dispatcher', $dispatcher);

        $result = $process->validate();

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertEquals($expected, $result->getErrors());
    }

    /**
     * Provide data for testing 'getPreviousResponse' method
     *
     * @return array
     */
    public function getPreviousResponseProvider()
    {
        $responses = [
            $this->createMock(Response::class),
            $this->createMock(Response::class),
        ];

        $set = EntitySet::forClass(Response::class, $responses, 0, EntitySet::ALLOW_DUPLICATES);

        return [
            [$responses, $responses[1]],
            [$set, $responses[1]],
            [new EntitySet(), null],
            [[], null],
        ];
    }

    /**
     * Test 'getPreviousResponse' method
     *
     * @dataProvider getPreviousResponseProvider
     */
    public function testGetPreviousResponse($responses, $expected)
    {
        $this->process->previous = $responses;

        $result = $this->process->getPreviousResponse();

        $this->assertSame($expected, $result);
    }

    /**
     * Test 'getFinishedStates' method
     */
    public function testGetFinishedStates()
    {
        $result = Process::getFinishedStates();

        $this->assertEquals([':success', ':failed', ':cancelled'], $result);
    }

    /**
     * Test 'jsonSerialize' method
     */
    public function testJsonSerialize()
    {
        $result = json_decode(json_encode($this->process));

        $this->assertTrue(isset($result->{'$schema'}));
        $this->assertFalse(isset($result->schema));
    }
}