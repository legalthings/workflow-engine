<?php

namespace JsonSchema\Validator;

use Scenario;
use Action;
use State;
use DataInstruction;
use AvailableResponse;
use StateTransition;
use Asset;
use AssocEntitySet;
use Jasny\DB\EntitySet;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

/**
 * @covers JsonSchema\Validator\Wrapper
 */
class WrapperTest extends \Codeception\Test\Unit
{
    use \Jasny\TestHelper;

    /**
     * Test '__invoke' method
     */
    public function testInvoke()
    {
        $validator = $this->createMock(Validator::class);
        $repository = $this->createMock(Repository::class);
        $scenario = $this->getScenario();
        $expectedData = $this->getExpectedData();
        $schema = (object)['foo' => 'bar'];

        $repository->expects($this->once())->method('get')->with($scenario->schema)->willReturn($schema);
        $validator->expects($this->once())->method('reset');
        $validator->expects($this->once())->method('validate')
            ->with($expectedData, $schema, Constraint::CHECK_MODE_EXCEPTIONS);

        $validatorWrapper = new Wrapper($validator, $repository);
        $validatorWrapper($scenario);
    }

    /**
     * Test '__invoke' method, if schema is not found
     */
    public function testInvokeNullSchema()
    {
        $validator = $this->createMock(Validator::class);
        $repository = $this->createMock(Repository::class);
        $scenario = $this->getScenario();

        $repository->expects($this->once())->method('get')->with($scenario->schema)->willReturn(null);
        $validator->expects($this->never())->method('reset');
        $validator->expects($this->never())->method('validate');

        $validatorWrapper = new Wrapper($validator, $repository);
        $validatorWrapper($scenario);
    }

    /**
     * Get test scenario
     *
     * @return Scenario
     */
    protected function getScenario()
    {
        $scenario = (new Scenario)->setValues(['schema' => 'http://schema.scenario']);

        $actions = [
            (new Action)->setValues([
                'schema' => 'http://schema.action.foo',
                'key' => 'foo',
                'title' => 'Foo action',
                'actors' => ['system', 'default'],
                'condition' => (new DataInstruction)->setValues(['<eval>' => 'test.condition']),
                'responses' => [
                    'ok' => (new AvailableResponse)->setValues(['key' => 'ok'])
                ]
            ]),
            (new Action)->setValues([
                'schema' => 'http://schema.action.bar',
                'key' => 'bar',
                'title' => 'Bar action',
                'actors' => ['user'],
                'description' => (new DataInstruction)->setValues(['<eval>' => 'test.label']),
                'responses' => [
                    'ok' => (new AvailableResponse)->setValues(['key' => 'ok']),
                    'error' => (new AvailableResponse)->setValues(['key' => 'error'])
                ]
            ]),
        ];

        $states = new AssocEntitySet([
            (new State)->setValues([
                'key' => 'baz',
                'instructions' => [
                    (new DataInstruction)->setValues(['<eval>' => 'test.instruction']),
                    (new DataInstruction)->setValues(['<eval>' => 'test.instruction2'])
                ],
                'transitions' => new EntitySet([
                    (new StateTransition)->setValues([
                        'action' => 'foo',
                        'condition' => (new DataInstruction)->setValues(['<eval>' => 'true'])
                    ])
                ], null, EntitySet::ALLOW_DUPLICATES)
            ])
        ]);

        $assets = [
            (new Asset)->setValues([
                'schema' => 'http://schema.asset.foo',
                'key' => 'foo_asset'
            ])
        ];

        $scenario->actions = $actions;
        $scenario->states = $states;
        $scenario->assets = $assets;

        return $scenario;
    }

    /**
     * Get data, obtained by casting entities to json
     *
     * @return array
     */
    protected function getExpectedData()
    {
        $actions = [
            [
                'key' => 'foo',
                'title' => 'Foo action',
                'actors' => ['system', 'default'],
                'condition' => ['<eval>' => 'test.condition'],
                'responses' => [
                    'ok' => [
                        'title' => null,
                        'display' => 'always',
                        'update' => []
                    ]
                ],
                'label' => null,
                'description' => null,
                'default_response' => 'ok',
                'determine_response' => null,
                '$schema' => 'http://schema.action.foo'
            ],
            [
                'key' => 'bar',
                'title' => 'Bar action',
                'actors' => ['user'],
                'description' => ['<eval>' => 'test.label'],
                'responses' => [
                    'ok' => [
                        'title' => null,
                        'display' => 'always',
                        'update' => []
                    ],
                    'error' => [
                        'title' => null,
                        'display' => 'always',
                        'update' => []
                    ]
                ],
                'label' => null,
                'condition' => true,
                'default_response' => 'ok',
                'determine_response' => null,
                '$schema' => 'http://schema.action.bar'
            ]
        ];

        $states = (object)[
            'baz' => [
                'instructions' => [
                    ['<eval>' => 'test.instruction'],
                    ['<eval>' => 'test.instruction2']
                ],
                'transitions' => [
                    [
                        'action' => 'foo',
                        'condition' => ['<eval>' => 'true'],
                        'response' => null,
                        'transition' => null
                    ]
                ],
                'key' => 'baz',
                'title' => null,
                'description' => null,
                'actions' => [],
                'timeout' => null,
                'display' => 'always'
            ]
        ];

        $assets = [
            [
                '$schema' => 'http://schema.asset.foo',
                'key' => 'foo_asset'
            ]
        ];

        return objectify([
            '$schema' => 'http://schema.scenario',
            'id' => null,
            'title' => null,
            'description' => null,
            'actors' => (object)[],
            'allow_actions' => [],
            'definitions' => (object)[],
            'meta' => (object)[],
            'actions' => $actions,
            'states' => $states,
            'assets' => $assets
        ]);
    }
}
