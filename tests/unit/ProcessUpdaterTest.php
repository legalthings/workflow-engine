<?php

use PHPUnit\Framework\MockObject\MockObject;
use Jasny\ValidationResult;
use Jasny\DB\EntitySet;

/**
 * @covers ProcessUpdater
 */
class ProcessUpdaterTest extends \Codeception\Test\Unit
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
                    'response' => 'ok',
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

    /**
     * @return Process&MockObject
     */
    protected function createProcess(): Process
    {
        /** @var Process&MockObject $process */
        $process = $this->getMockBuilder(Process::class)
            ->setMethods(['validate'])
            ->enableOriginalConstructor()
            ->getMock();
        $process->id = '00000000-0000-0000-0000-000000000000';

        $process->scenario = $this->createScenario();

        $process->actors['manager'] = new Actor();
        $process->actors['client'] = new Actor();

        $process->current = new CurrentState();
        $process->current->key = ':initial';
        $process->current->actions['first'] = clone $process->scenario->actions['first'];
        $process->current->transitions[] = StateTransition::fromData(['transition' => 'basic_step']);

        return $process;
    }

    protected function createCurrentState(Scenario $scenario, string $key)
    {
        $state = new CurrentState();
        $state->key = $key;

        foreach ($scenario->states[$key]->actions as $actionKey) {
            $state->actions[$actionKey] = clone $scenario->actions[$actionKey];
        }

        $state->transitions = clone $scenario->states[$key]->transitions;

        return $state;
    }


    public function testUpdate()
    {
        $process = $this->createProcess();
        $process->expects($this->atLeastOnce())->method('validate')->willReturn(ValidationResult::success());
        $scenario = $process->scenario;

        $oldState = clone $process->current;
        $basicStepState = $this->createCurrentState($scenario, 'basic_step');
        $next = new EntitySet();

        $process->current->response = new Response();
        $process->current->response->key = 'ok';
        $process->current->response->action = clone $process->current->actions['first'];

        $stateInstantiator = $this->createMock(StateInstantiator::class);
        $stateInstantiator->expects($this->exactly(2))->method('instantiate')
            ->withConsecutive(
                [$this->identicalTo($scenario->states[':initial'])],
                [$this->identicalTo($scenario->states['basic_step'])]
            )
            ->willReturnOnConsecutiveCalls($oldState, $basicStepState);

        $patcher = $this->createMock(DataPatcher::class);
        $patcher->expects($this->never())->method('project');
        $patcher->expects($this->never())->method('set');

        $simulate = $this->createMock(ProcessSimulator::class);
        $simulate->expects($this->once())->method('getNextStates')
            ->with($this->identicalTo($process))
            ->willReturn($next);

        $updater = new ProcessUpdater($stateInstantiator, $patcher, $simulate);

        $validation = $updater->update($process);

        $this->assertEquals(ValidationResult::success(), $validation);
        $this->assertSame($basicStepState, $process->current);
        $this->assertSame($next, $process->next);

        return $process;
    }

    /**
     * @depends testUpdate
     */
    public function testUpdateNoStateChange(Process $process)
    {
        $scenario = $process->scenario;

        $oldState = clone $process->current;
        $next = new EntitySet();

        $process->current->response = new Response();
        $process->current->response->key = 'nop'; // Unknown response
        $process->current->response->action = clone $process->current->actions['second'];

        $stateInstantiator = $this->createMock(StateInstantiator::class);
        $stateInstantiator->expects($this->once())->method('instantiate')
            ->with($this->identicalTo($scenario->states['basic_step']))
            ->willReturn($oldState);

        $patcher = $this->createMock(DataPatcher::class);
        $patcher->expects($this->never())->method('project');
        $patcher->expects($this->never())->method('set');

        $simulate = $this->createMock(ProcessSimulator::class);
        $simulate->expects($this->once())->method('getNextStates')
            ->with($this->identicalTo($process))
            ->willReturn($next);

        $updater = new ProcessUpdater($stateInstantiator, $patcher, $simulate);

        $validation = $updater->update($process);

        $this->assertEquals(ValidationResult::success(), $validation);
        $this->assertSame($oldState, $process->current);
        $this->assertSame($next, $process->next);
    }

    /**
     * @depends testUpdate
     */
    public function testUpdateAlt(Process $process)
    {
        $scenario = $process->scenario;

        $oldState = clone $process->current;
        $altStateStep = $this->createCurrentState($scenario, 'alt_step');
        $next = new EntitySet();

        $process->current->response = new Response();
        $process->current->response->key = 'ok';
        $process->current->response->action = clone $process->current->actions['alt'];

        $stateInstantiator = $this->createMock(StateInstantiator::class);
        $stateInstantiator->expects($this->exactly(2))->method('instantiate')
            ->withConsecutive(
                [$this->identicalTo($scenario->states['basic_step'])],
                [$this->identicalTo($scenario->states['alt_step'])]
            )
            ->willReturnOnConsecutiveCalls($oldState, $altStateStep);

        $patcher = $this->createMock(DataPatcher::class);
        $patcher->expects($this->never())->method('project');
        $patcher->expects($this->never())->method('set');

        $simulate = $this->createMock(ProcessSimulator::class);
        $simulate->expects($this->once())->method('getNextStates')
            ->with($this->identicalTo($process))
            ->willReturn($next);

        $updater = new ProcessUpdater($stateInstantiator, $patcher, $simulate);

        $validation = $updater->update($process);

        $this->assertEquals(ValidationResult::success(), $validation);
        $this->assertSame($altStateStep, $process->current);
        $this->assertSame($next, $process->next);

        return $process;
    }

    /**
     * @depends testUpdateAlt
     */
    public function testUpdateCancel(Process $process)
    {
        $scenario = $process->scenario;

        $oldState = clone $process->current;
        $cancelledState = CurrentState::fromData(['cancelled' => true]);
        $next = new EntitySet();

        $process->current->response = new Response();
        $process->current->response->key = 'cancel';
        $process->current->response->action = clone $process->current->actions['alt'];

        $stateInstantiator = $this->createMock(StateInstantiator::class);
        $stateInstantiator->expects($this->exactly(2))->method('instantiate')
            ->withConsecutive(
                [$this->identicalTo($scenario->states['alt_step'])],
                [$this->identicalTo($scenario->states[':cancelled'])]
            )
            ->willReturnOnConsecutiveCalls($oldState, $cancelledState);

        $patcher = $this->createMock(DataPatcher::class);
        $patcher->expects($this->never())->method('project');
        $patcher->expects($this->never())->method('set');

        $simulate = $this->createMock(ProcessSimulator::class);
        $simulate->expects($this->once())->method('getNextStates')
            ->with($this->identicalTo($process))
            ->willReturn($next);

        $updater = new ProcessUpdater($stateInstantiator, $patcher, $simulate);

        $validation = $updater->update($process);

        $this->assertEquals(ValidationResult::success(), $validation);
        $this->assertSame($cancelledState, $process->current);
        $this->assertSame($next, $process->next);
    }
}
