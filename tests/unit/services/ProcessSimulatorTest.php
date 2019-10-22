<?php

use LegalThings\DataEnricher;
use Jasny\DB\EntitySet;

/**
 * @covers ProcessSimulator
 */
class ProcessSimulatorTest extends \Codeception\Test\Unit
{
    use Jasny\TestHelper;

    protected function createScenario(): Scenario
    {
        $scenario = new Scenario();
        $scenario->title = 'Do the test';

        $scenario->states['initial'] = State::fromData([
            'title' => 'Initial',
            'transitions' => [
                [
                    'on' => 'first',
                    'goto' => 'basic_step',
                ],
            ],
        ]);
        $scenario->states['basic_step'] = State::fromData([
            'title' => 'Step 1',
            'description' => 'Describe this step',
            'timeout' => '6h',
            'transitions' => [
                [
                    'on' => 'second',
                    'goto' => ':success',
                ],
                [
                    'on' => 'alt',
                    'goto' => 'alt_step',
                ],
                [
                    'on' => ':timeout',
                    'goto' => ':failed'
                ]
            ]
        ]);
        $scenario->states['alt_step'] = State::fromData([
            'title' => 'Alternative route',
            'transitions' => [
                [
                    'on' => 'alt.cancel',
                    'goto' => ':cancelled',
                ],
                [
                    'on' => 'alt.retry',
                    'goto' => 'basic_step',
                ],
                [
                    'on' => 'skip',
                    'goto' => ':success',
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
        $process->id = '00000000-0000-0000-0000-000000000000';

        $process->scenario = $this->createScenario();

        $process->actors['manager'] = new Actor();
        $process->actors['client'] = new Actor();

        $process->current = new CurrentState();
        $process->current->key = 'initial';

        return $process;
    }

    public function testGoldenFlow()
    {
        $process = $this->createProcess();

        $dataEnricher = $this->createMock(DataEnricher::class);
        $dataEnricher->expects($this->exactly(2))->method('applyTo')
            ->with($this->isInstanceOf(NextState::class), $this->isInstanceOf(Process::class));

        $actionInstantiator = $this->createMock(ActionInstantiator::class);

        $simulator = new ProcessSimulator($dataEnricher, $actionInstantiator);

        $next = $simulator->getNextStates($process);
        $this->assertInstanceOf(EntitySet::class, $next);

        $this->assertEquals(['basic_step', ':success'], $next->key);

        $this->assertInstanceOf(NextState::class, $next[0]);
        $this->assertEquals('basic_step', $next[0]->key);
        $this->assertEquals('Step 1', $next[0]->title);
        $this->assertEquals('Describe this step', $next[0]->description);
        $this->assertEquals('6h', $next[0]->timeout);
        $this->assertEquals(['manager'], $next[0]->actors);

        $this->assertInstanceOf(NextState::class, $next[1]);
        $this->assertEquals(':success', $next[1]->key);
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
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     */
    public function testStartingFromAltStep(string $defaultResponse, array $expected)
    {
        $process = $this->createProcess();
        $process->current->key = 'alt_step';

        $process->scenario->actions['alt']->default_response = $defaultResponse;

        $dataEnricher = $this->createMock(DataEnricher::class);
        $dataEnricher->expects($this->exactly(count($expected)))->method('applyTo')
            ->with($this->isInstanceOf(NextState::class), $this->isInstanceOf(Process::class));

        $actionInstantiator = $this->createMock(ActionInstantiator::class);

        $simulator = new ProcessSimulator($dataEnricher, $actionInstantiator);

        $next = $simulator->getNextStates($process);

        $this->assertEquals($expected, $next->key);
    }

    /**
     * Add condition for 'alt' action in 'alt_step' state and remove response.
     */
    protected function modifyScenarioSetTransactionConditions(Scenario $scenario)
    {
        $altTransitions = $scenario->states['alt_step']->transitions;

        $altTransitions[0]->on = 'alt';
        $altTransitions[0]->condition = DataInstruction::fromData(['<eval>' => 'false']);

        $altTransitions[1]->on = 'alt';
        $altTransitions[1]->condition = DataInstruction::fromData(['<eval>' => 'true']);
    }

    public function testWithTransactionCondition()
    {
        $process = $this->createProcess();
        $process->current->key = 'alt_step';

        $this->modifyScenarioSetTransactionConditions($process->scenario);

        $dataEnricher = $this->createMock(DataEnricher::class);
        $dataEnricher->expects($this->any())->method('applyTo')
            ->willReturnCallback(function($subject) {
                if ($subject instanceof StateTransition && $subject->condition instanceof DataInstruction) {
                    $subject->condition = ($subject->condition->getValues() === ['<eval>' => 'true']);
                }

                return $subject;
            });

        $actionInstantiator = $this->createMock(ActionInstantiator::class);

        $simulator = new ProcessSimulator($dataEnricher, $actionInstantiator);

        $next = $simulator->getNextStates($process);

        $this->assertEquals(['basic_step', ':success'], $next->key);
    }

    public function actionConditionProvider()
    {
        return [
            ['true', [':cancelled']],
            ['false', [':success']],
        ];
    }

    /**
     * @dataProvider actionConditionProvider
     */
    public function testWithActionCondition(string $condition, array $expected)
    {
        $process = $this->createProcess();
        $process->current->key = 'alt_step';

        $process->scenario->actions['alt']->default_response = 'cancel';
        $process->scenario->actions['alt']->condition = DataInstruction::fromData(['<eval>' => $condition]);

        $dataEnricher = $this->createMock(DataEnricher::class);

        $actionInstantiator = $this->createMock(ActionInstantiator::class);
        $actionInstantiator->expects($this->any())->method('enrichAction')
            ->willReturnCallback(function($subject) {
                if ($subject instanceof Action && $subject->condition instanceof DataInstruction) {
                    $subject->condition = ($subject->condition->getValues() === ['<eval>' => 'true']);
                }

                return $subject;
            });

        $simulator = new ProcessSimulator($dataEnricher, $actionInstantiator);

        $next = $simulator->getNextStates($process);

        $this->assertEquals($expected, $next->key);
    }

    public function testWithLoop()
    {
        $process = $this->createProcess();
        $process->current->key = 'basic_step';

        // Alt action is the only available. Default alt is to retry.
        $process->scenario->actions['second']->condition = false;
        $process->scenario->actions['alt']->default_response = 'retry';

        $dataEnricher = $this->createMock(DataEnricher::class);

        $actionInstantiator = $this->createMock(ActionInstantiator::class);
        $actionInstantiator->expects($this->any())->method('enrichAction')
            ->with($this->isInstanceOf(Action::class), $this->isInstanceOf(Process::class))
            ->will($this->returnCallback(function($action) {
                return $action;
            }));

        $simulator = new ProcessSimulator($dataEnricher, $actionInstantiator);

        $next = $simulator->getNextStates($process);

        $this->assertEquals(['alt_step', 'basic_step', 'alt_step', ':loop'], $next->key);
    }

    public function testTransitionRuntimeException()
    {
        $process = $this->createProcess();

        // Emulate an invalid data instruction
        $transition = $process->scenario->states['basic_step']->transitions[0];
        $transition->condition = DataInstruction::fromData(['<eval>' => '99x']);

        $dataEnricher = $this->createMock(DataEnricher::class);
        $dataEnricher->expects($this->any())->method('applyTo')
            ->willReturnCallback(function($subject) {
                if ($subject instanceof StateTransition && $subject->condition instanceof DataInstruction) {
                    throw new RuntimeException("Invalid data instruction");
                }

                return $subject;
            });

        $actionInstantiator = $this->createMock(ActionInstantiator::class);

        $simulator = new ProcessSimulator($dataEnricher, $actionInstantiator);

        $next = @$simulator->getNextStates($process);

        $this->assertLastError(E_USER_WARNING, "Error while getting transition for state " .
            "'basic_step' in process: '00000000-0000-0000-0000-000000000000': Invalid data instruction");

        $this->assertEquals(['basic_step'], $next->key);
    }

    public function testNextStateRuntimeException()
    {
        $process = $this->createProcess();

        // Emulate an invalid data instruction
        $process->scenario->actions['second']->actors = DataInstruction::fromData(['<eval>' => '99x']);

        $dataEnricher = $this->createMock(DataEnricher::class);
        $dataEnricher->expects($this->atLeastOnce())->method('applyTo')
            ->willReturnCallback(function($subject) {
                if ($subject instanceof NextState && $subject->actors instanceof DataInstruction) {
                    throw new RuntimeException("Invalid data instruction");
                }

                return $subject;
            });

        $actionInstantiator = $this->createMock(ActionInstantiator::class);

        $simulator = new ProcessSimulator($dataEnricher, $actionInstantiator);

        $next = @$simulator->getNextStates($process);

        $this->assertLastError(E_USER_WARNING, "Error while creating simulated state " .
            "'basic_step' in process: '00000000-0000-0000-0000-000000000000': Invalid data instruction");

        $this->assertEquals(['basic_step', ':success'], $next->key);
    }
}
