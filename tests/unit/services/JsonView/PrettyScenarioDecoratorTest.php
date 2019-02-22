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
        $expected = $this->getExpectedScenario('basic-user-and-system');
        $scenario = $this->createMock(Scenario::class);

        $decorator = new PrettyScenarioDecorator();
        $result = $decorator($scenario, $data);

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

        return json_decode($expectedJson);
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
            '$schema' => 'https://specs.livecontracts.io/v1.0.0/scenario/schema.json#',
            'title' => 'Basic system and user',
            'assets' => (object)[],
            'definitions' => (object)[],
            'allow_actions' => [],
            'meta' => (object)[]
        ];

        $scenario->actors = (object)[
            'user' => (object)[
                '$schema' => 'http://json-schema.org/draft-07/schema#',
                'key' => 'user',
                'title' => 'User'
            ],
            'system' => (object)[
                '$schema' => 'http://json-schema.org/draft-07/schema#',
                'key' => 'system',
                'title' => 'System',
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
        return (object)[
            'step1' => (object)[
                '$schema' => 'https://specs.livecontracts.io/v1.0.0/action/http/schema.json#',
                'key' => 'step1',
                'title' => 'Step1',
                'description' => 'Step1',
                'label' => 'Launch step 1',
                'actors' => ['system'],
                'url' => 'https://www.example.com',
                'responses' => (object)[
                    'ok' => [ ],
                    'error' => [ ]
                ]
            ],
            'step2' => (object)[
                '$schema' => 'https://specs.livecontracts.io/v1.0.0/action/nop/schema.json#',
                'key' => 'step2',
                'title' => 'Step2',
                'description' => 'Step2',
                'label' => 'Launch step 2',
                'trigger_response' => 'ok',
                'data' => 'second response',
                'actors' => ['system', 'user'],
                'responses' => (object)[
                    'ok' => [ ],
                    'error' => [ ]
                ]
            ],
            'step3' => (object)[
                '$schema' => 'https://specs.livecontracts.io/v1.0.0/action/schema.json#',
                'key' => 'step3',
                'title' => 'Step3',
                'description' => 'Step3',
                'label' => 'Launch step 3',
                'actors' => ['user'],
                'responses' => (object)[
                    'ok' => [ ],
                    'cancel' => [ ]
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
        return (object)[
            ':initial' => (object)[
                'key' => ':initial',
                'actions' => ['step1'],
                'title' => 'First state',
                'description' => 'First state',
                'instructions' => [],
                'timeout' => 'P1D',
                'display' => 'always',
                'transitions' => [
                    (object)[
                        'action' => 'step1',
                        'response' => 'ok',
                        'transition' => 'second'
                    ],
                    (object)[
                        'action' => 'step1',
                        'response' => 'error',
                        'transition' => ':failed'
                    ]
                ]
            ],
            'second' => (object)[
                'key' => 'second',
                'actions' => ['step2'],
                'title' => 'Second state',
                'description' => 'Second state',
                'instructions' => [],
                'timeout' => 'P1D',
                'display' => 'always',
                'transitions' => [
                    (object)[
                        'action' => 'step2',
                        'response' => 'ok',
                        'transition' => 'third'
                    ],
                    (object)[
                        'action' => 'step2',
                        'response' => 'error',
                        'transition' => ':failed'
                    ]
                ]
            ],
            'third' => (object)[
                'key' => 'third',
                'actions' => ['step3'],
                'title' => 'Third state',
                'description' => 'Third state',
                'instructions' => [],
                'timeout' => 'P1D',
                'display' => 'always',
                'transitions' => [
                    (object)[
                        'transition' => ':success'
                    ]
                ]
            ]
        ];
    }
}
