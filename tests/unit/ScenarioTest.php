<?php

use Codeception\TestCase\Test;
use Jasny\ValidationResult;
use Jasny\DB\EntitySet;

/**
 * @covers Scenario
 */
class ScenarioTest extends Test
{
    public function testConstruction()
    {
        $scenario = new Scenario();

        $this->assertAttributeInstanceOf(AssocEntitySet::class, 'actors', $scenario);
        $this->assertSame(JsonSchema::class, $scenario->actors->getEntityClass());

        $this->assertAttributeInstanceOf(AssocEntitySet::class, 'assets', $scenario);
        $this->assertSame(JsonSchema::class, $scenario->assets->getEntityClass());

        $this->assertAttributeInstanceOf(AssetSet::class, 'definitions', $scenario);
        $this->assertSame(Asset::class, $scenario->definitions->getEntityClass());

        $this->assertAttributeInstanceOf(AssocEntitySet::class, 'actions', $scenario);
        $this->assertSame(Action::class, $scenario->actions->getEntityClass());

        $this->assertAttributeInstanceOf(AssocEntitySet::class, 'states', $scenario);
        $this->assertSame(State::class, $scenario->states->getEntityClass());

        $this->assertContainsOnlyInstancesOf(State::class, $scenario->states->getArrayCopy());
        $this->assertArrayHasKey(':success', $scenario->states->getArrayCopy());
        $this->assertArrayHasKey(':failed', $scenario->states->getArrayCopy());
        $this->assertArrayHasKey(':cancelled', $scenario->states->getArrayCopy());
    }

    public function testGetActor()
    {
        $scenario = new Scenario();

        $manager = new JsonSchema(['$ref' => 'https://specs.livecontracts.io/v0.1.0/actor/schema.json#']);
        $scenario->actors['manager'] = $manager;

        $worker = new JsonSchema(['$ref' => 'https://specs.livecontracts.io/v0.1.0/actor/schema.json#']);
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

        $manager = new JsonSchema(['$ref' => 'https://specs.livecontracts.io/v0.1.0/actor/schema.json#']);
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
        $scenario->states[':initial'] = $initial;

        $foo = new State();
        $scenario->states['foo'] = $foo;

        $bar = new State();
        $scenario->states['bar'] = $bar;

        $this->assertSame($initial, $scenario->getState(':initial'));
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

        $this->assertAttributeInstanceOf(AssocEntitySet::class, 'actions', $scenario);
        $this->assertSame(Action::class, $scenario->actions->getEntityClass());
        $this->assertArrayHasKey('one', $scenario->actions->getArrayCopy());
        $this->assertArrayHasKey('two', $scenario->actions->getArrayCopy());
        $this->assertAttributeEquals('action 1', 'title', $scenario->actions['one']);

        $this->assertCount(2, $scenario->actions);
    }

    /**
     * @dataProvider uncastedScenarioProvider
     */
    public function testCastStates(Scenario $scenario)
    {
        $scenario->cast();

        $this->assertAttributeInstanceOf(AssocEntitySet::class, 'states', $scenario);
        $this->assertSame(State::class, $scenario->states->getEntityClass());
        $this->assertArrayHasKey('foo', $scenario->states->getArrayCopy());
        $this->assertArrayHasKey('bar', $scenario->states->getArrayCopy());
        $this->assertAttributeEquals('state Foo', 'title', $scenario->states['foo']);

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

        $this->assertAttributeInstanceOf(AssocEntitySet::class, 'actors', $scenario);
        $this->assertSame(JsonSchema::class, $scenario->actors->getEntityClass());
        $this->assertArrayHasKey('manager', $scenario->actors->getArrayCopy());
        $this->assertArrayHasKey('client', $scenario->actors->getArrayCopy());
        $this->assertAttributeEquals('Client', 'title', $scenario->actors['client']);

        $this->assertCount(2, $scenario->actors);
    }

    /**
     * @dataProvider uncastedScenarioProvider
     */
    public function testCastAssets(Scenario $scenario)
    {
        $scenario->cast();

        $this->assertAttributeInstanceOf(AssocEntitySet::class, 'assets', $scenario);
        $this->assertSame(JsonSchema::class, $scenario->assets->getEntityClass());
        $this->assertArrayHasKey('report', $scenario->assets->getArrayCopy());
        $this->assertAttributeEquals('XYZ Report', 'title', $scenario->assets['report']);

        $this->assertCount(1, $scenario->assets);
    }


    public function testValidation()
    {
        $scenario = new Scenario();
        $scenario->start = null;

        $scenario->actions['foo'] = $this->createMock(Action::class);
        $scenario->actions['foo']
            ->expects($this->once())
            ->method('validate')
            ->willReturn(ValidationResult::error("'ok' response is required"));

        $scenario->states['foo'] = $this->createMock(State::class);
        $scenario->states['foo']
            ->expects($this->once())
            ->method('validate')
            ->willReturn(ValidationResult::error("state is invalid"));

        $validation = $scenario->validate();
        $errors = $validation->getErrors();

        $expected = [
            "schema is required",
            "scenario must have an ':initial' state",
            "id is required",
            "action 'foo': 'ok' response is required",
            "state 'foo': state is invalid",
        ];

        $this->assertEquals($expected, $errors, '', 0.0, 0, true);
    }

    public function testToData()
    {
        $scenario = new Scenario();

        $scenario->schema = "http://specs.livecontracts.io/draft-01/01-core/schema.json#";
        $scenario->id = '7d7d0444-f6d7-473e-b715-f5cd8a3cc632';
        $scenario->title = "A unit test case";
        $scenario->description = "This scenario is for testing";
        $scenario->allow_actions = ['bar'];

        $scenario->actions['foo'] = $this->createMock(Action::class);
        $scenario->actions['foo']->expects($this->atLeastOnce())->method('toData')
            ->willReturn(['title' => "Foo", 'responses' => [['key' => 'ok', 'transition' => 'bar']]]);

        $scenario->actions['bar'] = $this->createMock(Action::class);
        $scenario->actions['bar']->expects($this->atLeastOnce())->method('toData')
            ->willReturn(['title' => "Bar", 'responses' => [['key' => 'ok', 'transition' => ':success']]]);

        $scenario->actors['manager'] = $this->createMock(JsonSchema::class);
        $scenario->actors['manager']->expects($this->atLeastOnce())->method('toData')
            ->willReturn(['title' => "Operational manager"]);

        $scenario->states[':initial'] = $this->createMock(State::class);
        $scenario->states[':initial']->expects($this->atLeastOnce())->method('toData')
            ->willReturn(['title' => "First", 'actions' => ['foo']]);

        $expected = [
            '_id' => '7d7d0444-f6d7-473e-b715-f5cd8a3cc632',
            'schema' => 'http://specs.livecontracts.io/draft-01/01-core/schema.json#',
            'title' => 'A unit test case',
            'description' => 'This scenario is for testing',
            'actions' => [
                [
                    'key' => 'foo',
                    'title' => 'Foo',
                    'responses' => [
                        [
                            'key' => 'ok',
                            'transition' => 'bar'
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
                    'key' => ':initial',
                    'title' => 'First',
                    'actions' => ['foo'],
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

            ],
            'definitions' => [],
            'info' => [
                'schema' => 'http://json-schema.org/draft-07/schema#',
                'type' => 'object',
                'properties' => []
            ],
            'meta' => (object)[],
        ];

        $this->assertEquals($expected, $scenario->toData());
    }

    /**
     * @todo improve this test
     */
    public function testFromData()
    {
        $scenario = Scenario::fromData([
            '_id' => 'foo-bar',
            'actions' => [
                [
                    'key' => 'foo',
                    'title' => 'Foo',
                ],
                [
                    'key' => 'bar',
                    'title' => 'Bar',
                ]
            ],
            'states' => [
                [
                    'key' => ':initial',
                    'title' => 'First',
                    'actions' => ['foo']
                ],
                [
                    'key' => 'bar',
                    'title' => 'Second',
                    'actions' => ['bar'],
                ],
            ],
        ]);

        $this->assertAttributeEquals('foo-bar', 'id', $scenario);

        $this->assertArrayHasKey('foo', $scenario->actions->getArrayCopy());
        $this->assertInstanceOf(Action::class, $scenario->actions['foo']);
        $this->assertAttributeEquals('Foo', 'title', $scenario->actions['foo']);

        $this->assertArrayHasKey(':initial', $scenario->states->getArrayCopy());
    }

    public function testJsonSerialize()
    {
        $scenario = new Scenario();

        $scenario->schema = 'http://specs.livecontracts.io/draft-01/01-core/schema.json#';
        $scenario->id = '7d7d0444-f6d7-473e-b715-f5cd8a3cc632';
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

        $scenario->states[':initial'] = $this->createMock(State::class);
        $scenario->states[':initial']->expects($this->once())->method('jsonSerialize')
            ->willReturn([
                'title' => "First",
                'actions' => ['foo'],
                'transitions' => [
                    ['transition' => ':success']
                ]
            ]);

        $expected = [
            '$schema' => 'http://specs.livecontracts.io/draft-01/01-core/schema.json#',
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
            'states' => (object)[
                ':initial' => (object)[
                    'title' => 'First',
                    'actions' => ['foo'],
                    'instructions' => [],
                    'transitions' => [
                        ['transition' => ':success']
                    ],
                ],
            ],
            'actors' => [
                'manager' => [
                    'title' => 'Operational manager'
                ]
            ],
            'assets' => [],
            'definitions' => [],
            'info' => [],
        ];

        $serialized = json_encode($scenario);
        $this->assertEquals($expected, json_decode($serialized, true));
    }
}
