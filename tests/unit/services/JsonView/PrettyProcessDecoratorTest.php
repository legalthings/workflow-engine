<?php

namespace JsonView;

use Process;
use stdClass;

/**
 * @covers JsonView\PrettyProcessDecorator
 */
class PrettyProcessDecoratorTest extends \Codeception\Test\Unit
{
    /**
     * Test 'getJsonOptions' method
     */
    public function testGetJsonOptions()
    {
        $decorator = new PrettyProcessDecorator();
        $result = $decorator->getJsonOptions();

        $this->assertSame(\JSON_PRETTY_PRINT, $result);
    }

    /**
     * Test '__invoke' method
     */
    public function testInvoke()
    {
        $data = $this->getData();
        $expected = $this->getExpectedProcess('basic-user-and-system_second-state');
        $process = $this->createMock(Process::class);

        $decorator = new PrettyProcessDecorator();
        $result = $decorator($process, $data);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test '__invoke' method, when current state has single transition
     */
    public function testInvokeCurrentSingleTransition()
    {
        $data = $this->getData();
        $expected = $this->getExpectedProcess('basic-user-and-system_second-state');
        $process = $this->createMock(Process::class);

        unset($data->current->transitions[1]);

        $expected->current->transition = $expected->current->transitions[0];
        unset($expected->current->transitions);

        $decorator = new PrettyProcessDecorator();
        $result = $decorator($process, $data);

        $this->assertEquals($expected, $result);
    }

    /**
     * Get expected process data
     *
     * @return stdClass
     */
    protected function getExpectedProcess($name): stdClass
    {
        $expectedJson = file_get_contents(__DIR__ . "/../../../_data/processes/$name.json");
        $expected = json_decode($expectedJson);

        $expected->actors = (array)$expected->actors;

        return $expected;
    }

    /**
     * Get test process
     *
     * @return stdClass
     */
    protected function getData(): stdClass
    {
        $process = (object)[
            'id' => '4527288f-108e-fk69-8d2d-7914ffd93894',
            'scenario' => (object)['id' => '2557288f-108e-4398-8d2d-7914ffd93150'],
            'schema' => 'https://specs.livecontracts.io/v1.0.0/process/schema.json#',
            'title' => 'Basic system and user process',
            'assets' => (object)[],
            'definitions' => (object)[],
            'meta' => (object)[]
        ];

        $process->actors = [
            'user' => (object)[
                '$schema' => 'https://specs.livecontracts.io/v1.0.0/asset/actor.json#',
                'key' => 'user',
                'title' => 'User'
            ],
            'system' => (object)[
                '$schema' => 'https://specs.livecontracts.io/v1.0.0/asset/actor.json#',
                'key' => 'system',
                'title' => 'System',
                'signkeys' => [
                    '57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn'
                ],
                'identity' => '6uk7288s-afe4-7398-8dbh-7914ffd930pl'
            ]
        ];

        $process->previous = [
            (object)[
                '$schema' => 'https://specs.livecontracts.io/v1.0.0/response/schema.json#',
                'title' => null,
                'action' => (object)[
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
                'key' => 'ok',
                'display' => 'always',
                'data' => (object)[
                    'foo' => 'bar'
                ],
                'actor' => (object)[
                    'key' => 'system',
                    'title' => 'System',
                ]
            ]
        ];

        $process->current = (object)[
            'key' => 'second',
            'title' => 'Second state',
            'description' => 'Second state',
            'display' => 'always',
            'actions' => [
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
                ]
            ],
            'transitions' => [
                (object)[
                    'action' => 'step2',
                    'response' => 'ok',
                    'transition' => 'third',
                ],
                (object)[
                    'action' => 'step2',
                    'response' => 'error',
                    'transition' => ':failed',
                ]
            ]
        ];

        $process->next = [
            (object)[
                'key' => 'third',
                'display' => 'always',
                'actors' => ['user']
            ],
            (object)[
                'key' => ':success',
                'display' => 'always',
                'actors' => []
            ]
        ];

        return $process;   
    }
}
