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

        $scenario->states['initial'] = State::fromData([
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

        $actorSchema1 = new JsonSchema([
            'type' => 'object',
            'properties' => [
                'key' => [
                    'type' => 'string',
                    'default' => 'manager'
                ]
            ]
        ]);

        $actorSchema2 = new JsonSchema([
            'type' => 'object',
            'properties' => [
                'key' => [
                    'type' => 'string',
                    'default' => 'client'
                ]
            ]
        ]);

        $process->scenario->actors = new AssocEntitySet([
            'manager' => $actorSchema1, 
            'client' => $actorSchema2
        ]);

        $process->current = new CurrentState();
        $process->current->key = 'initial';
        $process->current->actions['first'] = clone $process->scenario->actions['first'];
        $process->current->transitions[] = StateTransition::fromData(['transition' => 'basic_step']);

        return $process;
    }

    protected function createCurrentState(Scenario $scenario, string $key): CurrentState
    {
        $state = new CurrentState();
        $state->key = $key;

        foreach ($scenario->states[$key]->actions as $actionKey) {
            $state->actions[$actionKey] = clone $scenario->actions[$actionKey];
        }

        $state->transitions = clone $scenario->states[$key]->transitions;

        return $state;
    }

    protected function createResponse(CurrentState $current, string $actionKey, string $responseKey, $data = null)
    {
        $response = new Response();

        $response->key = $responseKey;
        $response->action = clone $current->actions[$actionKey];
        $response->data = $data;

        return $response;
    }

    /**
     * @return StateInstantiator&MockObject
     */
    protected function createStateInstantiatorMock(
        Process $process,
        ?string $stateKey = null,
        ?CurrentState $processState = null
    ): StateInstantiator {
        $stateInstantiator = $this->createMock(StateInstantiator::class);

        if ($stateKey === null) {
            $stateInstantiator->expects($this->never())->method('instantiate');
        } else {
            $stateInstantiator->expects($this->once())->method('instantiate')
                ->with($this->identicalTo($process->scenario->states[$stateKey]))
                ->willReturn($processState);
        }

        $stateInstantiator->expects($this->once())->method('recalcActions')->with($process);
        $stateInstantiator->expects($this->once())->method('recalcTransitions')->with($process);

        return $stateInstantiator;
    }

    /**
     * @return DataPatcher&MockObject
     */
    protected function createDataPatcherMock(): DataPatcher
    {
        $patcher = $this->createMock(DataPatcher::class);
        $patcher->expects($this->never())->method('project');
        $patcher->expects($this->never())->method('set');

        return $patcher;
    }

    /**
     * @return ProcessSimulator&MockObject
     */
    protected function createProcessSimulatorMock(Process $process, ?EntitySet $next = null): ProcessSimulator
    {
        $simulator = $this->createMock(ProcessSimulator::class);

        if ($next === null) {
            $simulator->expects($this->never())->method('getNextStates');
        } else {
            $simulator->expects($this->once())->method('getNextStates')
                ->with($this->identicalTo($process))
                ->willReturn($next);
        }

        return $simulator;
    }

    /**
     * All methods that depend on `testUpdate` work in sequence, stepping through the same process.
     */
    public function testUpdate()
    {
        $process = $this->createProcess();
        $process->expects($this->atLeastOnce())->method('validate')->willReturn(ValidationResult::success());
        $scenario = $process->scenario;

        $basicStepState = $this->createCurrentState($scenario, 'basic_step');
        $next = new EntitySet();

        $response = $this->createResponse($process->current,'first', 'ok');
        $process->current->response = $response;

        $stateInstantiator = $this->createStateInstantiatorMock($process, 'basic_step', $basicStepState);
        $patcher = $this->createDataPatcherMock();
        $simulator = $this->createProcessSimulatorMock($process, $next);

        $updater = new ProcessUpdater($stateInstantiator, $patcher, $simulator);

        $validation = $updater->update($process);

        $this->assertEquals(ValidationResult::success(), $validation);
        $this->assertSame($basicStepState, $process->current);
        $this->assertSame($next, $process->next);

        $this->assertCount(1, $process->previous);
        $this->assertEquals($response, $process->previous[0]);

        return $process;
    }

    /**
     * @depends testUpdate
     */
    public function testUpdateNoStateChange(Process $process)
    {
        $next = new EntitySet();

        $response = $this->createResponse($process->current,'second', 'nop');
        $process->current->response = $response;

        $stateInstantiator = $this->createStateInstantiatorMock($process);
        $patcher = $this->createDataPatcherMock();
        $simulator = $this->createProcessSimulatorMock($process, $next);

        $updater = new ProcessUpdater($stateInstantiator, $patcher, $simulator);

        $validation = $updater->update($process);

        $this->assertEquals(ValidationResult::success(), $validation);
        $this->assertEquals('basic_step', $process->current->key);
        $this->assertSame($next, $process->next);

        $this->assertCount(2, $process->previous);
        $this->assertEquals('first.ok', $process->previous[0]->getRef());
        $this->assertEquals($response, $process->previous[1]);
    }

    /**
     * @depends testUpdate
     */
    public function testUpdateAlt(Process $process)
    {
        $scenario = $process->scenario;

        $altStepState = $this->createCurrentState($scenario, 'alt_step');
        $next = new EntitySet();

        $response = $this->createResponse($process->current,'alt', 'ok');
        $process->current->response = $response;

        $stateInstantiator = $this->createStateInstantiatorMock($process, 'alt_step', $altStepState);
        $patcher = $this->createDataPatcherMock();
        $simulator = $this->createProcessSimulatorMock($process, $next);

        $updater = new ProcessUpdater($stateInstantiator, $patcher, $simulator);

        $validation = $updater->update($process);

        $this->assertEquals(ValidationResult::success(), $validation);
        $this->assertSame($altStepState, $process->current);
        $this->assertSame($next, $process->next);

        $this->assertCount(3, $process->previous);
        $this->assertEquals('first.ok', $process->previous[0]->getRef());
        $this->assertEquals('second.nop', $process->previous[1]->getRef());
        $this->assertEquals($response, $process->previous[2]);
    }

    /**
     * @depends testUpdate
     */
    public function testUpdateCancel(Process $process)
    {
        $scenario = $process->scenario;

        $cancelledState = $this->createCurrentState($scenario, ':cancelled');
        $next = new EntitySet();

        $response = $this->createResponse($process->current,'alt', 'cancel');
        $process->current->response = $response;

        $stateInstantiator = $this->createStateInstantiatorMock($process, ':cancelled', $cancelledState);
        $patcher = $this->createDataPatcherMock();
        $simulator = $this->createProcessSimulatorMock($process, $next);

        $updater = new ProcessUpdater($stateInstantiator, $patcher, $simulator);

        $validation = $updater->update($process);

        $this->assertEquals(ValidationResult::success(), $validation);
        $this->assertSame($cancelledState, $process->current);
        $this->assertSame($next, $process->next);

        $this->assertCount(4, $process->previous);
        $this->assertEquals('first.ok', $process->previous[0]->getRef());
        $this->assertEquals('second.nop', $process->previous[1]->getRef());
        $this->assertEquals('alt.ok', $process->previous[2]->getRef());
        $this->assertEquals($response, $process->previous[3]);
    }

    public function testUpdateWithUpdateInstructions()
    {
        $process = $this->createProcess();
        $process->expects($this->once())->method('validate')->willReturn(ValidationResult::success());
        $scenario = $process->scenario;

        $currentResponse = $process->current->actions['first']->responses['ok'];
        $currentResponse->update = EntitySet::forClass(
            UpdateInstruction::class,
            [
                UpdateInstruction::fromData(['select' => 'assets.data']),
                UpdateInstruction::fromData([
                    'select' => 'actors.manager.name',
                    'projection' => 'name',
                    'patch' => false,
                ]),
                UpdateInstruction::fromData([
                    'select' => 'actors.client.name',
                    'projection' => 'first_name',
                    'data' => ['first_name' => 'John'],
                    'patch' => true,
                ]),
            ],
            0,
            EntitySet::ALLOW_DUPLICATES
        );

        $basicStepState = $this->createCurrentState($scenario, 'basic_step');
        $next = new EntitySet();

        $response = $this->createResponse($process->current,'first', 'ok', ['name' => 'Jane', 'age' => 42]);
        $process->current->response = $response;

        $stateInstantiator = $this->createStateInstantiatorMock($process, 'basic_step', $basicStepState);
        $simulator = $this->createProcessSimulatorMock($process, $next);

        $patcher = $this->createMock(DataPatcher::class);
        $patcher->expects($this->exactly(2))->method('project')
            ->withConsecutive(
                [['name' => 'Jane', 'age' => 42], 'name'],
                [(object)['first_name' => 'John'], 'first_name']
            )
            ->willReturnOnConsecutiveCalls('Jane', 'John');
        $patcher->expects($this->exactly(3))->method('set')
            ->withConsecutive(
                [$this->identicalTo($process), 'assets.data', ['name' => 'Jane', 'age' => 42], true],
                [$this->identicalTo($process), 'actors.manager.name', 'Jane', false],
                [$this->identicalTo($process), 'actors.client.name', 'John', true]
            )
            ->willReturnOnConsecutiveCalls(
                $this->callback(function(Process $process, $select, $data) {
                    $process->assets['data'] = $data;
                }),
                $this->callback(function(Process $process, $select, $data) {
                    $process->actors['manager']->name = $data;
                }),
                $this->callback(function(Process $process, $select, $data) {
                    $process->assets['client']->name = $data;
                })
            );

        $updater = new ProcessUpdater($stateInstantiator, $patcher, $simulator);

        $validation = $updater->update($process);

        $this->assertEquals(ValidationResult::success(), $validation);
        $this->assertSame($basicStepState, $process->current);
        $this->assertSame($next, $process->next);

        return $process;
    }

    public function testUpdateValidationFailed()
    {
        $process = $this->createProcess();
        $process->expects($this->atLeastOnce())->method('validate')
            ->willReturn(ValidationResult::error('some error'));
        $scenario = $process->scenario;

        $basicStepState = $this->createCurrentState($scenario, 'basic_step');

        $process->current->response = $this->createResponse($process->current,'first', 'ok');

        $stateInstantiator = $this->createMock(StateInstantiator::class);
        $stateInstantiator->expects($this->never())->method('instantiate');
        $stateInstantiator->expects($this->once())->method('recalcActions')->with($process);
        $stateInstantiator->expects($this->never())->method('recalcTransitions');

        $patcher = $this->createDataPatcherMock();
        $simulator = $this->createProcessSimulatorMock($process);

        $updater = new ProcessUpdater($stateInstantiator, $patcher, $simulator);

        $validation = $updater->update($process);

        $this->assertEquals(ValidationResult::error('some error'), $validation);
        $this->assertNotSame($basicStepState, $process->current);
        $this->assertEquals('initial', $process->current->key);

        return $process;
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Can't update process '00000000-0000-0000-0000-000000000000' in state 'initial' without a response
     */
    public function testUpdateNoResponse()
    {
        $process = $this->createProcess();

        $stateInstantiator = $this->createMock(StateInstantiator::class);
        $patcher = $this->createMock(DataPatcher::class);
        $simulator = $this->createMock(ProcessSimulator::class);

        $updater = new ProcessUpdater($stateInstantiator, $patcher, $simulator);

        $updater->update($process);
    }
}
