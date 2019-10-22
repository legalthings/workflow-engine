<?php

use Jasny\ValidationResult;
use Jasny\EventDispatcher\EventDispatcher;
use Jasny\DB\EntitySet;

/**
 * @covers Scenario
 */
class ScenarioTest extends \Codeception\Test\Unit
{
    use Jasny\TestHelper;

    /**
     * @var Scenario
     **/
    protected $scenario;

    /**
     * Execute actions before each test case
     */
    public function _before()
    {
        $this->scenario = new Scenario();
    }

    public function testConstruction()
    {
        $scenario = new Scenario();

        $this->assertInstanceOf(AssocEntitySet::class, $scenario->actors);
        $this->assertSame(JsonSchema::class, $scenario->actors->getEntityClass());

        $this->assertInstanceOf(AssocEntitySet::class, $scenario->assets);
        $this->assertSame(JsonSchema::class, $scenario->assets->getEntityClass());

        $this->assertInstanceOf(AssocEntitySet::class, $scenario->definitions);
        $this->assertSame(Asset::class, $scenario->definitions->getEntityClass());

        $this->assertInstanceOf(AssocEntitySet::class, $scenario->actions);
        $this->assertSame(Action::class, $scenario->actions->getEntityClass());

        $this->assertInstanceOf(AssocEntitySet::class, $scenario->states);
        $this->assertSame(State::class, $scenario->states->getEntityClass());

        $this->assertContainsOnlyInstancesOf(State::class, $scenario->states->getArrayCopy());
        $this->assertArrayHasKey(':success', $scenario->states->getArrayCopy());
        $this->assertArrayHasKey(':failed', $scenario->states->getArrayCopy());
        $this->assertArrayHasKey(':cancelled', $scenario->states->getArrayCopy());
    }

    public function testGetActor()
    {
        $scenario = new Scenario();

        $manager = new JsonSchema(['$ref' => 'https://specs.letsflow.io/v0.3.0/actor#']);
        $scenario->actors['manager'] = $manager;

        $worker = new JsonSchema(['$ref' => 'https://specs.letsflow.io/v0.3.0/actor#']);
        $scenario->actors['worker'] = $worker;

        $this->assertSame($manager, $scenario->getActor('manager'));
        $this->assertSame($worker, $scenario->getActor('worker'));
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Scenario doesn't have a 'walker' actor
     */
    public function testGetUnknownActor()
    {
        $scenario = new Scenario();

        $manager = new JsonSchema(['$ref' => 'https://specs.letsflow.io/v0.3.0/actor#']);
        $scenario->actors['manager'] = $manager;

        $scenario->getActor('walker');
    }

    public function testGetAction()
    {
        $scenario = new Scenario();

        $foo = new Action();
        $scenario->actions['foo'] = $foo;

        $bar = new Action();
        $scenario->actions['bar'] = $bar;

        $this->assertSame($foo, $scenario->getAction('foo'));
        $this->assertSame($bar, $scenario->getAction('bar'));
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Scenario doesn't have a 'qux' action
     */
    public function testGetUnknownAction()
    {
        $scenario = new Scenario();
        $scenario->getAction('qux');
    }


    public function testGetState()
    {
        $scenario = new Scenario();

        $initial = new State();
        $scenario->states['initial'] = $initial;

        $foo = new State();
        $scenario->states['foo'] = $foo;

        $bar = new State();
        $scenario->states['bar'] = $bar;

        $this->assertSame($initial, $scenario->getState('initial'));
        $this->assertSame($foo, $scenario->getState('foo'));
        $this->assertSame($bar, $scenario->getState('bar'));
    }


    public function testGetImplicitStates()
    {
        $scenario = new Scenario();

        $this->assertInstanceOf(State::class, $scenario->getState(':success'));
        $this->assertInstanceOf(State::class, $scenario->getState(':failed'));
        $this->assertInstanceOf(State::class, $scenario->getState(':cancelled'));
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Scenario doesn't have a 'qux' state
     */
    public function testGetUnknownState()
    {
        $scenario = new Scenario();
        $scenario->getState('qux');
    }


    public function uncastedScenarioProvider()
    {
        $scenario = new Scenario();

        $scenario->actions = [
            'one' => ['title' => 'action 1'],
            'two' => ['title' => 'action 2'],
        ];
        $scenario->states = [
            'foo' => ['title' => 'state Foo'],
            'bar' => ['title' => 'state Bar'],
        ];
        $scenario->actors = [
            'manager' => ['title' => 'Manager'],
            'client' => ['title' => 'Client'],
        ];
        $scenario->assets = [
            'report' => ['title' => 'XYZ Report'],
        ];
        $scenario->definitions = [
            'books' => [
                ['title' => 'The Hobbit'],
                ['title' => 'Harry Potter'],
            ],
            'dimensions' => [
                'width' => 800,
                'height' => 600,
            ],
        ];

        return [
            [$scenario],
        ];
    }

    /**
     * @dataProvider uncastedScenarioProvider
     */
    public function testCastActions(Scenario $scenario)
    {
        $scenario->cast();

        $this->assertInstanceOf(AssocEntitySet::class, $scenario->actions);
        $this->assertSame(Action::class, $scenario->actions->getEntityClass());
        $this->assertArrayHasKey('one', $scenario->actions->getArrayCopy());
        $this->assertArrayHasKey('two', $scenario->actions->getArrayCopy());
        $this->assertEquals('action 1', $scenario->actions['one']->title);

        $this->assertCount(2, $scenario->actions);
    }

    /**
     * @dataProvider uncastedScenarioProvider
     */
    public function testCastStates(Scenario $scenario)
    {
        $scenario->cast();

        $this->assertInstanceOf(AssocEntitySet::class, $scenario->states);
        $this->assertSame(State::class, $scenario->states->getEntityClass());
        $this->assertArrayHasKey('foo', $scenario->states->getArrayCopy());
        $this->assertArrayHasKey('bar', $scenario->states->getArrayCopy());
        $this->assertEquals('state Foo', $scenario->states['foo']->title);

        $this->assertArrayHasKey(':success', $scenario->states->getArrayCopy());
        $this->assertArrayHasKey(':failed', $scenario->states->getArrayCopy());
        $this->assertArrayHasKey(':cancelled', $scenario->states->getArrayCopy());

        $this->assertCount(5, $scenario->states);
    }

    /**
     * @dataProvider uncastedScenarioProvider
     */
    public function testCastActors(Scenario $scenario)
    {
        $scenario->cast();

        $this->assertInstanceOf(AssocEntitySet::class, $scenario->actors);
        $this->assertSame(JsonSchema::class, $scenario->actors->getEntityClass());
        $this->assertArrayHasKey('manager', $scenario->actors->getArrayCopy());
        $this->assertArrayHasKey('client', $scenario->actors->getArrayCopy());
        $this->assertEquals('Client', $scenario->actors['client']->title);

        $this->assertCount(2, $scenario->actors);
    }

    /**
     * @dataProvider uncastedScenarioProvider
     */
    public function testCastAssets(Scenario $scenario)
    {
        $scenario->cast();

        $this->assertInstanceOf(AssocEntitySet::class, $scenario->assets);
        $this->assertSame(JsonSchema::class, $scenario->assets->getEntityClass());
        $this->assertArrayHasKey('report', $scenario->assets->getArrayCopy());
        $this->assertEquals('XYZ Report', $scenario->assets['report']->title);

        $this->assertCount(1, $scenario->assets);
    }

    /**
     * @dataProvider uncastedScenarioProvider
     */
    public function testCastEvent(Scenario $scenario)
    {
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects($this->once())->method('trigger')
            ->with('cast', $this->identicalTo($scenario));

        $scenario->setDispatcher($dispatcher);

        $scenario->cast();
    }


    protected function assertScenario(Scenario $scenario)
    {
        $this->assertEquals('7d7d0444-f6d7-473e-b715-f5cd8a3cc632', $scenario->id);
        $this->assertEquals(
            'https://specs.letsflow.io/v0.3.0/scenario#',
            $scenario->schema
        );
        $this->assertEquals('A unit test case', $scenario->title);
        $this->assertEquals('This scenario is for testing', $scenario->description);
    }

    protected function assertScenarioActions(Scenario $scenario)
    {
        $this->assertInstanceOf(AssocEntitySet::class, $scenario->actions);
        $this->assertEquals(Action::class, $scenario->actions->getEntityClass());

        $this->assertArrayHasKey('foo', $scenario->actions->getArrayCopy());
        $this->assertInstanceOf(Action::class, $scenario->actions['foo']);
        $this->assertEquals('Foo', $scenario->actions['foo']->title);
        $this->assertInstanceOf(AssocEntitySet::class, $scenario->actions['foo']->responses);
        $this->assertArrayHasKey('ok', $scenario->actions['foo']->responses->getArrayCopy());

        $this->assertArrayHasKey('bar', $scenario->actions->getArrayCopy());
        $this->assertInstanceOf(Action::class, $scenario->actions['bar']);
        $this->assertEquals('Bar', $scenario->actions['bar']->title);
        $this->assertInstanceOf(AssocEntitySet::class, $scenario->actions['bar']->responses);
        $this->assertArrayHasKey('ok', $scenario->actions['bar']->responses->getArrayCopy());
    }

    protected function assertScenarioStates(Scenario $scenario)
    {
        $this->assertInstanceOf(AssocEntitySet::class, $scenario->states);
        $this->assertEquals(State::class, $scenario->states->getEntityClass());

        $this->assertArrayHasKey('initial', $scenario->states->getArrayCopy());
        $this->assertInstanceOf(State::class, $scenario->states['initial']);
        $this->assertEquals('First', $scenario->states['initial']->title);

        $this->assertCount(1, $scenario->states['initial']->transitions);
        $this->assertEquals('foo.ok', $scenario->states['initial']->transitions[0]->on);
    }

    protected function assertScenarioActors(Scenario $scenario)
    {
        $this->assertInstanceOf(AssocEntitySet::class, $scenario->actors);
        $this->assertEquals(JsonSchema::class, $scenario->actors->getEntityClass());

        $this->assertArrayHasKey('manager', $scenario->actors->getArrayCopy());
        $this->assertInstanceOf(JsonSchema::class, $scenario->actors['manager']);
        $this->assertEquals('Operational manager', $scenario->actors['manager']->title);
    }

    protected function assertScenarioAssets(Scenario $scenario)
    {
        $this->assertInstanceOf(AssocEntitySet::class, $scenario->assets);
        $this->assertEquals(JsonSchema::class, $scenario->assets->getEntityClass());

        $this->assertArrayHasKey('report', $scenario->assets->getArrayCopy());
        $this->assertInstanceOf(JsonSchema::class, $scenario->assets['report']);
        $this->assertEquals('http://json-schema.org/draft-07/schema#', $scenario->assets['report']->schema);
        $this->assertEquals('object', $scenario->assets['report']->type);
    }

    protected function assertScenarioDefinitions(Scenario $scenario)
    {
        $this->assertInstanceOf(AssocEntitySet::class, $scenario->definitions);
        $this->assertEquals(Asset::class, $scenario->definitions->getEntityClass());

        $this->assertArrayHasKey('dimensions', $scenario->definitions->getArrayCopy());
        $this->assertInstanceOf(Asset::class, $scenario->definitions['dimensions']);
        $this->assertEquals(10, $scenario->definitions['dimensions']->height);
        $this->assertEquals(15, $scenario->definitions['dimensions']->width);
    }


    public function valuesProvider()
    {
        $fullScenario = [
            'id' => '7d7d0444-f6d7-473e-b715-f5cd8a3cc632',
            '$schema' => 'https://specs.letsflow.io/v0.3.0/scenario#',
            'title' => 'A unit test case',
            'description' => 'This scenario is for testing',
            'foo' => 'bar', // to be ignored
            'actions' => [
                'foo' => [
                    'title' => 'Foo',
                    'responses' => [
                        'ok' => [
                        ],
                    ],
                ],
                'bar' => [
                    'title' => 'Bar',
                    'responses' => [
                        'ok' => [
                            'display' => 'once',
                            'update' => [
                                'select' => 'assets.report.name',
                                'data' => 'Bar report',
                            ],
                        ],
                    ],
                ],
            ],
            'allow_actions' => ['bar'],
            'states' => [
                'initial' => [
                    'title' => 'First',
                    'transitions' => [
                        [
                            'on' => 'foo.ok',
                            'goto' => 'second',
                        ],
                    ],
                ],
                'second' => [
                    'title' => 'Second',
                    'transitions' => [
                        [
                            'on' => 'bar',
                            'goto' => ':success',
                        ],
                    ],
                ],
            ],
            'actors' => [
                [
                    'key' => 'manager',
                    'title' => 'Operational manager',
                ],
            ],
            'assets' => [
                'report' => [
                    '$schema' => 'http://json-schema.org/draft-07/schema#',
                    'type' => 'object',
                ],
            ],
            'definitions' => [
                'dimensions' => [
                    'height' => 10,
                    'width' => 15,
                ],
            ],
        ];

        $compactScenario = [
            'id' => '7d7d0444-f6d7-473e-b715-f5cd8a3cc632',
            '$schema' => 'https://specs.letsflow.io/v0.3.0/scenario#',
            'title' => 'A unit test case',
            'description' => 'This scenario is for testing',
            'foo' => 'bar', // to be ignored
            'actions' => [
                'foo' => [
                    'title' => 'Foo',
                ],
                'bar' => [
                    'title' => 'Bar',
                    'display' => 'once',
                    'update' => [
                        'select' => 'assets.report.name',
                        'data' => 'Bar report',
                    ],
                ],
            ],
            'allow_actions' => ['bar'],
            'states' => [
                [
                    'key' => 'initial',
                    'title' => 'First',
                    'transitions' => [
                        [
                            'on' => 'foo.ok',
                            'goto' => 'second',
                        ],
                    ],
                    [
                        'key' => 'second',
                        'title' => 'Second',
                        'on' => 'bar',
                        'goto' => ':success',
                    ],
                ],
            ],
            'actors' => [
                [
                    'key' => 'manager',
                    'title' => 'Operational manager',
                ],
            ],
            'assets' => [
                'report' => [
                    '$schema' => 'http://json-schema.org/draft-07/schema#',
                    'type' => 'object',
                ],
            ],
            'definitions' => [
                'dimensions' => [
                    'height' => 10,
                    'width' => 15,
                ],
            ],
        ];

        return [
            [$fullScenario],
            [$compactScenario],
        ];
    }

    /**
     * @dataProvider valuesProvider
     */
    public function testSetValues($values)
    {
        $scenario = new Scenario();
        $scenario->setValues($values);

        $this->assertScenario($scenario);
        $this->assertScenarioActions($scenario);
        $this->assertScenarioStates($scenario);
        $this->assertScenarioActors($scenario);
        $this->assertScenarioAssets($scenario);
        $this->assertScenarioDefinitions($scenario);
    }


    public function testValidation()
    {
        $scenario = new Scenario();

        $scenario->schema = 'bar';

        $scenario->actions['foo'] = $this->createMock(Action::class);
        $scenario->actions['foo']
            ->expects($this->once())
            ->method('validate')
            ->willReturn(ValidationResult::error("'ok' response is required"));

        $scenario->states['one'] = $this->createMock(State::class);
        $scenario->states['one']
            ->expects($this->once())
            ->method('validate')
            ->willReturn(ValidationResult::error("state is invalid"));

        $validation = $scenario->validate();
        $errors = $validation->getErrors();

        $expected = [
            "schema property value is not valid",
            "scenario must have an 'initial' state",
            "action 'foo': 'ok' response is required",
            "state 'one': state is invalid",
        ];

        $this->assertEquals($expected, $errors, '', 0.0, 0, true);
    }

    public function storedDataProvider(): array
    {
        $data = [
            '_id' => '7d7d0444-f6d7-473e-b715-f5cd8a3cc632',
            'schema' => 'https://specs.letsflow.io/v0.3.0/scenario#',
            'title' => 'A unit test case',
            'description' => 'This scenario is for testing',
            'actions' => [
                [
                    'key' => 'foo',
                    'title' => 'Foo',
                    'responses' => [
                        [
                            'key' => 'ok',
                        ]
                    ]
                ],
                [
                    'key' => 'bar',
                    'title' => 'Bar',
                    'responses' => [
                        [
                            'key' => 'ok',
                            'transition' => ':success'
                        ]
                    ]
                ]
            ],
            'states' => [
                [
                    'key' => ':success',
                    'transitions' => [],
                    'instructions' => [],
                    'display' => 'always',
                ],
                [
                    'key' => ':failed',
                    'transitions' => [],
                    'instructions' => [],
                    'display' => 'always',
                ],
                [
                    'key' => ':cancelled',
                    'transitions' => [],
                    'instructions' => [],
                    'display' => 'always',
                ],
                [
                    'key' => 'initial',
                    'title' => 'First',
                    'actions' => ['foo'],
                    'transitions' => [
                        [
                            'on' => 'foo.ok',
                            'goto' => ':success',
                        ],
                    ],
                    'instructions' => [
                        'manager' => 'Do something',
                    ],
                    'display' => 'always',
                ],
            ],
            'actors' => [
                [
                    'key' => 'manager',
                    'title' => 'Operational manager',
                ]
            ],
            'allow_actions' => [
                'bar'
            ],
            'assets' => [
                [
                    'key' => 'report',
                    'schema' => 'http://json-schema.org/draft-07/schema#',
                    'type' => 'object',
                ]
            ],
            'definitions' => [
                [
                    'key' => 'dimensions',
                    'height' => 10,
                    'width' => 15,
                ]
            ]
        ];

        return [
            [$data],
        ];
    }

    /**
     * @dataProvider storedDataProvider
     */
    public function testToData(array $expected)
    {
        $scenario = new Scenario();

        $scenario->schema = "https://specs.letsflow.io/v0.3.0/scenario#";
        $scenario->id = '7d7d0444-f6d7-473e-b715-f5cd8a3cc632';
        $scenario->title = "A unit test case";
        $scenario->description = "This scenario is for testing";
        $scenario->allow_actions = ['bar'];

        $scenario->actions['foo'] = $this->createMock(Action::class);
        $scenario->actions['foo']->expects($this->atLeastOnce())->method('toData')
            ->willReturn(['title' => "Foo", 'responses' => [['key' => 'ok']]]);

        $scenario->actions['bar'] = $this->createMock(Action::class);
        $scenario->actions['bar']->expects($this->atLeastOnce())->method('toData')
            ->willReturn(['title' => "Bar", 'responses' => [['key' => 'ok', 'transition' => ':success']]]);

        $scenario->states['initial'] = $this->createMock(State::class);
        $scenario->states['initial']->expects($this->atLeastOnce())->method('toData')
            ->willReturn([
                'title' => "First",
                'actions' => ['foo'],
                'transitions' => [
                    ['on' => 'foo.ok', 'goto' => ':success'],
                ],
                'instructions' => [
                    'manager' => 'Do something',
                ],
                'display' => 'always',
            ]);

        $scenario->actors['manager'] = $this->createMock(JsonSchema::class);
        $scenario->actors['manager']->expects($this->atLeastOnce())->method('toData')
            ->willReturn(['title' => "Operational manager"]);

        $scenario->assets['report'] = $this->createMock(JsonSchema::class);
        $scenario->assets['report']->expects($this->atLeastOnce())->method('toData')
            ->willReturn([
                'schema' => 'http://json-schema.org/draft-07/schema#',
                'type' => 'object',
            ]);

        $scenario->definitions['dimensions'] = $this->createMock(Asset::class);
        $scenario->definitions['dimensions']->expects($this->atLeastOnce())->method('toData')
            ->willReturn(['height' => 10, 'width' => 15]);

        $this->assertEquals($expected, $scenario->toData());
    }

    /**
     * @dataProvider storedDataProvider
     */
    public function testFromData(array $data)
    {
        $scenario = Scenario::fromData($data);

        $this->assertScenario($scenario);
        $this->assertScenarioActions($scenario);
        $this->assertScenarioStates($scenario);
        $this->assertScenarioActors($scenario);
        $this->assertScenarioAssets($scenario);
        $this->assertScenarioDefinitions($scenario);
    }


    public function testJsonSerialize()
    {
        $scenario = new Scenario();

        $scenario->schema = 'https://specs.letsflow.io/v0.3.0/scenario#';
        $scenario->id = '7d7d0444-f6d7-473e-b715-f5cd8a3cc632';
        $scenario->title = 'Foo Bar test';
        $scenario->description = 'This scenario is for testing';
        $scenario->allow_actions = ['bar'];

        $scenario->actions['foo'] = $this->createMock(Action::class);
        $scenario->actions['foo']->expects($this->once())->method('jsonSerialize')
            ->willReturn(['title' => "Foo"]);

        $scenario->actions['bar'] = $this->createMock(Action::class);
        $scenario->actions['bar']->expects($this->once())->method('jsonSerialize')
            ->willReturn(['title' => "Bar"]);

        $scenario->actors['manager'] = $this->createMock(JsonSchema::class);
        $scenario->actors['manager']->expects($this->once())->method('jsonSerialize')
            ->willReturn(['title' => "Operational manager"]);

        $scenario->states['initial'] = $this->createMock(State::class);
        $scenario->states['initial']->expects($this->once())->method('jsonSerialize')
            ->willReturn([
                'title' => "First",
                'actions' => ['foo'],
                'instructions' => [],
                'transitions' => [
                    ['transition' => ':success']
                ]
            ]);

        $scenario->assets['report'] = $this->createMock(JsonSchema::class);
        $scenario->assets['report']->expects($this->atLeastOnce())->method('jsonSerialize')
            ->willReturn([
                '$schema' => 'http://json-schema.org/draft-07/schema#',
                'type' => 'object',
            ]);

        $scenario->definitions['dimensions'] = $this->createMock(Asset::class);
        $scenario->definitions['dimensions']->expects($this->atLeastOnce())->method('jsonSerialize')
            ->willReturn(['height' => 10, 'width' => 15]);

        $expected = [
            '$schema' => 'https://specs.letsflow.io/v0.3.0/scenario#',
            'id' => '7d7d0444-f6d7-473e-b715-f5cd8a3cc632',
            'title' => 'Foo Bar test',
            'description' => 'This scenario is for testing',
            'actions' => [
                'foo' => [
                    'title' => 'Foo',
                ],
                'bar' => [
                    'title' => 'Bar',
                ]
            ],
            'states' => [
                'initial' => [
                    'title' => 'First',
                    'actions' => ['foo'],
                    'instructions' => [],
                    'transitions' => [
                        ['transition' => ':success'],
                    ],
                ],
            ],
            'actors' => [
                'manager' => [
                    'title' => 'Operational manager'
                ]
            ],
            'assets' => [
                'report' => [
                    '$schema' => 'http://json-schema.org/draft-07/schema#',
                    'type' => 'object',
                ]
            ],
            'definitions' => [
                'dimensions' => [
                    'height' => 10,
                    'width' => 15,
                ]
            ],
            'allow_actions' => ['bar'],
        ];

        $serialized = json_encode($scenario);
        $this->assertEquals($expected, json_decode($serialized, true));
    }

    public function testJsonSerializeBlank()
    {
        $expected = [
            '$schema' => null,
            'id' => null,
            'title' => null,
            'description' => null,
            'actions' => [],
            'states' => [],
            'actors' => [],
            'assets' => [],
            'definitions' => [],
            'allow_actions' => [],
        ];

        $serialized = json_encode($this->scenario);
        $this->assertEquals($expected, json_decode($serialized, true));
    }

    /**
     * Test 'dispatch' method
     */
    public function testDispatch()
    {
        $event = 'foo';
        $payload = ['foo' => 'bar'];
        $expected = 'baz';

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects($this->once())->method('trigger')->with(
            $event,
            $this->identicalTo($this->scenario),
            $payload
        )->willReturn($expected);

        $this->setPrivateProperty($this->scenario, 'dispatcher', $dispatcher);
        $result = $this->scenario->dispatch($event, $payload);

        $this->assertSame($expected, $result);
    }

    /**
     * Provide data for testing 'getActionsForState' method, using state key
     *
     * @return array
     */
    public function getActionsForStateByKeyProvider()
    {
        $actions = [
            'step1' => $this->createMock(Action::class),
            'step2' => $this->createMock(Action::class),
            'step3' => $this->createMock(Action::class),
            'step4' => $this->createMock(Action::class)
        ];

        $actions['step1']->key = 'step1';
        $actions['step2']->key = 'step2';
        $actions['step3']->key = 'step3';
        $actions['step4']->key = 'step4';

        return [
            [
                $actions, 
                [], 
                ['step1', 'step4', 'step5'], 
                [
                    'step1' => $actions['step1'],
                    'step4' => $actions['step4']
                ]
            ],
            [
                $actions, 
                ['step1', 'step4', 'step5'], 
                [], 
                [
                    'step1' => $actions['step1'],
                    'step4' => $actions['step4']
                ]
            ],
            [
                $actions, 
                ['step1', 'step4', 'step5'], 
                ['step1', 'step2', 'step4'], 
                [
                    'step1' => $actions['step1'],
                    'step2' => $actions['step2'],
                    'step4' => $actions['step4']
                ]
            ],
            [
                $actions, 
                ['step5'], 
                [], 
                []
            ],
            [
                $actions, 
                [], 
                [], 
                []
            ],
            [
                [], 
                ['step1'], 
                ['step1'], 
                []
            ],
        ];
    }

    /**
     * Test 'getActionsForState' method, using state key
     *
     * @dataProvider getActionsForStateByKeyProvider
     */
    public function testGetActionsForStateByKey($actions, $stateActions, $allowActions, $expected)
    {
        $this->scenario->actions = $actions;
        $this->scenario->allow_actions = $allowActions;
        $this->scenario->states = [
            'foo' => $this->createConfiguredMock(State::class, ['getActions' => []]),
            'bar' => $this->createConfiguredMock(State::class, ['getActions' => $stateActions])
        ];

        $result = $this->scenario->getActionsForState('bar');
        $asArray = $result->getArrayCopy();

        $this->assertInstanceOf(EntitySet::class, $result);
        $this->assertEquals($expected, $asArray);
    }

    /**
     * Test 'getActionsForState' method, using state object
     *
     * @dataProvider getActionsForStateByKeyProvider
     */
    public function testGetActionsForStateByValue($actions, $stateActions, $allowActions, $expected)
    {
        $this->scenario->actions = $actions;
        $this->scenario->allow_actions = $allowActions;

        $state = $this->createConfiguredMock(State::class, ['getActions' => $stateActions]);

        $result = $this->scenario->getActionsForState($state);
        $asArray = $result->getArrayCopy();

        $this->assertInstanceOf(EntitySet::class, $result);
        $this->assertEquals($expected, $asArray);
    }

    /**
     * Test 'getActionsForState' method, if state is not found
     *
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Scenario doesn't have a 'foo' state
     */
    public function testGetActionsForStateNotFound()
    {
        $this->scenario->getActionsForState('foo');
    }
}
