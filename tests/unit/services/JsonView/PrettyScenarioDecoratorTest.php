<?php

namespace JsonView;

use Scenario;
use stdClass;

/**
 * @covers JsonView\PrettyScenarioDecorator
 */
class PrettyScenarioDecoratorTest extends \Codeception\Test\Unit
{
    /**
     * Test 'getJsonOptions' method
     */
    public function testGetJsonOptions()
    {
        $decorator = new PrettyScenarioDecorator();
        $result = $decorator->getJsonOptions();

        $this->assertSame(\JSON_PRETTY_PRINT, $result);
    }

    /**
     * Test '__invoke' method
     */
    public function testInvoke()
    {
        $data = $this->getData();
        $expected = $this->getExpectedScenario('basic-user-and-system.update-instructions');
        $scenario = $this->createMock(Scenario::class);

        $decorator = new PrettyScenarioDecorator();
        $result = $decorator($scenario, $data);

        $expected->id = $data->id;

        $this->assertEquals($expected, $result);
    }

    /**
     * Get expected scenario data
     *
     * @return stdClass
     */
    protected function getExpectedScenario($name): stdClass
    {
        $expectedJson = file_get_contents(__DIR__ . "/../../../_data/scenarios/$name.json");
        $expected = json_decode($expectedJson);

        $expected->actors = (array)$expected->actors;
        $expected->actions = (array)$expected->actions;
        $expected->states = (array)$expected->states;

        foreach ($expected->actions as $key => $action) {
            $expected->actions[$key]->responses = $action->responses;
        }

        return $expected;
    }

    /**
     * Get test scenario
     *
     * @return stdClass
     */
    protected function getData(): stdClass
    {
        $scenario = (object)[
            'id' => '2557288f-108e-4398-8d2d-7914ffd93150',
            '$schema' => 'https://specs.letsflow.io/v0.3.0/scenario#',
            'title' => 'Basic system and user with update instructions',
            'assets' => (object)[],
            'definitions' => (object)[],
            'allow_actions' => [],
        ];

        $scenario->actors = [
            'user' => (object)[
                '$schema' => 'http://json-schema.org/draft-07/schema#',
                'key' => 'user',
                'title' => 'User'
            ],
            'organization' => (object)[
                '$schema' => 'http://json-schema.org/draft-07/schema#',
                'key' => 'organization',
                'title' => 'Organization',
                'signkeys' => [
                    '57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn'
                ],
                'identity' => '6uk7288s-afe4-7398-8dbh-7914ffd930pl'
            ]
        ];

        $scenario->actions = $this->getActionsData();
        $scenario->states = $this->getStatesData();     

        return $scenario;   
    }

    /**
     * Get scenarion actions data
     *
     * @return array
     */
    protected function getActionsData()
    {
        return [
            'step1' => (object)[
                '$schema' => 'https://specs.letsflow.io/v0.3.0/action/http#',
                'key' => 'step1',
                'title' => 'Step1',
                'description' => 'Step1',
                'label' => 'Launch step 1',
                'actors' => ['organization'],
                'url' => 'https://www.example.com',
                'responses' => (object)[
                    'ok' => (object)[
                        'update' => [
                            (object)['select' => 'foo'],
                            (object)['select' => 'baz', 'patch' => true],
                            (object)['select' => 'bar', 'patch' => false],
                        ]
                    ],
                    'error' => (object)[]
                ]
            ],
            'step2' => (object)[
                '$schema' => 'https://specs.letsflow.io/v0.3.0/action/nop#',
                'key' => 'step2',
                'title' => 'Step2',
                'description' => 'Step2',
                'label' => 'Launch step 2',
                'trigger_response' => 'ok',
                'data' => 'second response',
                'actors' => ['organization', 'user'],
                'responses' => (object)[
                    'ok' => (object)[
                        'update' => [
                            (object)['select' => 'bar']
                        ]
                    ],
                    'error' => (object)[]
                ]
            ],
            'step3' => (object)[
                '$schema' => 'https://specs.letsflow.io/v0.3.0/action#',
                'key' => 'step3',
                'title' => 'Step3',
                'description' => 'Step3',
                'label' => 'Launch step 3',
                'actors' => ['user'],
                'responses' => (object)[
                    'ok' => (object)[
                        'update' => [
                            (object)['select' => 'bar', 'projection' => '{id: test}', 'patch' => true],
                        ]
                    ],
                    'cancel' => (object)[]
                ]
            ]
        ];
    }

    /**
     * Get scenario states data
     *
     * @return array
     */
    protected function getStatesData()
    {
        return [
            'initial' => (object)[
                'key' => 'initial',
                'title' => 'First state',
                'description' => 'First state',
                'instructions' => [],
                'timeout' => 'P1D',
                'display' => 'always',
                'transitions' => [
                    (object)[
                        'on' => 'step1.ok',
                        'goto' => 'second'
                    ],
                    (object)[
                        'on' => 'step1.error',
                        'goto' => ':failed'
                    ]
                ]
            ],
            'second' => (object)[
                'key' => 'second',
                'title' => 'Second state',
                'description' => 'Second state',
                'instructions' => [],
                'timeout' => 'P1D',
                'display' => 'always',
                'transitions' => [
                    (object)[
                        'on' => 'step2.ok',
                        'goto' => 'third'
                    ],
                    (object)[
                        'on' => 'step2.error',
                        'goto' => ':failed'
                    ]
                ]
            ],
            'third' => (object)[
                'key' => 'third',
                'title' => 'Third state',
                'description' => 'Third state',
                'instructions' => [],
                'timeout' => 'P1D',
                'display' => 'always',
                'transitions' => [
                    (object)[
                        'on' => 'step3',
                        'goto' => ':success'
                    ]
                ]
            ]
        ];
    }
}
