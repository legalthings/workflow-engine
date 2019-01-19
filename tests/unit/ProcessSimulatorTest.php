<?php

use LegalThings\DataEnricher;
use Jasny\DB\EntitySet;

/**
 * @covers ProcessSimulator
 */
class ProcessSimulatorTest extends \Codeception\Test\Unit
{
    protected function createScenario(): Scenario
    {
        $scenario = new Scenario();
        $scenario->title = 'Do the test';

        $scenario->states[':initial'] = State::fromData([
            'title' => 'Initial',
            'actions' => ['first'],
            'transitions' => [
                ['transition' => 'basic_step'],
            ],
        ]);
        $scenario->states['basic_step'] = State::fromData([
            'title' => 'Step 1',
            'description' => 'Describe this step',
            'timeout' => '6h',
            'actions' => ['second', 'alt'],
            'transitions' => [
                [
                    'action' => 'second',
                    'transition' => ':success',
                ],
                [
                    'action' => 'alt',
                    'transition' => 'alt_step',
                ],
                [
                    'action' => ':timeout',
                    'transition' => ':failed'
                ]
            ]
        ]);
        $scenario->states['alt_step'] = State::fromData([
            'title' => 'Alternative route',
            'actions' => ['alt', 'skip'],
            'transitions' => [
                [
                    'action' => 'alt',
                    'response' => 'cancel',
                    'transition' => ':cancelled',
                ],
                [
                    'action' => 'alt',
                    'response' => 'retry',
                    'transition' => 'basic_step',
                ],
                [
                    'action' => 'skip',
                    'transition' => ':success',
                ],
            ]
        ]);

        $scenario->actions['first'] = new Action();
        $scenario->actions['second'] = Action::fromData([
            'actors' => ['manager'],
        ]);
        $scenario->actions['alt'] = Action::fromData([
            'actors' => ['client', 'manager'],
        ]);
        $scenario->actions['skip'] = Action::fromData([
            'actors' => ['manager'],
        ]);

        return $scenario;
    }

    protected function createProcess(): Process
    {
        $process = new Process();
        $process->scenario = $this->createScenario();

        $process->actors['manager'] = new Actor();
        $process->actors['client'] = new Actor();

        $process->current = new CurrentState();
        $process->current->key = ':initial';

        return $process;
    }

    public function testGoldenFlow()
    {
        $process = $this->createProcess();

        $dataEnricher = $this->createMock(DataEnricher::class);
        $dataEnricher->expects($this->any())->method('applyTo')
            ->with($this->isInstanceOf(NextState::class), $this->isInstanceOf(Process::class))
            ->willReturnArgument(0);

        $simulator = new ProcessSimulator($dataEnricher);

        $next = $simulator->getNextStates($process);
        $this->assertInstanceOf(EntitySet::class, $next);

        $this->assertEquals(['basic_step', ':success'], $next->key);

        $this->assertInstanceOf(NextState::class, $next[0]);
        $this->assertAttributeEquals('basic_step', 'key', $next[0]);
        $this->assertAttributeEquals('Step 1', 'title', $next[0]);
        $this->assertAttributeEquals('Describe this step', 'description', $next[0]);
        $this->assertAttributeEquals('6h', 'timeout', $next[0]);
        $this->assertAttributeEquals(['manager'], 'actors', $next[0]);

        $this->assertInstanceOf(NextState::class, $next[1]);
        $this->assertAttributeEquals(':success', 'key', $next[1]);
    }

    public function altResponseProvider()
    {
        return [
            ['cancel', [':cancelled']],
            ['retry', ['basic_step', ':success']],
        ];
    }

    /**
     * @dataProvider altResponseProvider
     */
    public function testStartingFromAltStep(string $defaultResponse, array $expected)
    {
        $process = $this->createProcess();
        $process->current->key = 'alt_step';

        $process->scenario->actions['alt']->default_response = $defaultResponse;

        $dataEnricher = $this->createMock(DataEnricher::class);
        $dataEnricher->expects($this->any())->method('applyTo')
            ->with($this->isInstanceOf(NextState::class), $this->isInstanceOf(Process::class))
            ->willReturnArgument(0);

        $simulator = new ProcessSimulator($dataEnricher);

        $next = $simulator->getNextStates($process);

        $this->assertEquals($expected, $next->key);
    }
}
