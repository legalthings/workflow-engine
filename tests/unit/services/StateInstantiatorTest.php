<?php

use LegalThings\DataEnricher;
use PHPUnit\Framework\MockObject\MockObject;
use Carbon\CarbonImmutable;

/**
 * @covers StateInstantiator
 */
class StateInstantiatorTest extends \Codeception\Test\Unit
{
    /**
     * @var DataEnricher&MockObject
     */
    protected $enricher;

    /**
     * @var ActionInstantiator
     **/
    protected $actionInstantiator;

    /**
     * @var StateInstantiator
     */
    protected $stateInstantiator;

    public function _before()
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2018, 1, 1, 0, 0, 0, 'UTC'));

        $this->enricher = $this->createMock(DataEnricher::class);
        $this->actionInstantiator = $this->createMock(ActionInstantiator::class);
        $this->stateInstantiator = new StateInstantiator($this->enricher, $this->actionInstantiator);
    }

    public function _after()
    {
        CarbonImmutable::setTestNow(null);
    }

    public function testInstantiate()
    {
        $action = Action::fromData([
            'title' => 'Fill out the form',
            'actors' => ['client'],
        ]);

        $actor = new Actor();
        $actor->key = 'foo';

        $scenario = new Scenario();
        $scenario->actions['fill-out-form'] = $action;

        $process = new Process();
        $process->scenario = $scenario;
        $process->current = new CurrentState();
        $process->current->response = new Response();
        $process->current->response->actor = $actor;

        $state = State::fromData([
            'key' => 'client-form',
            'title' => 'Fill out the form',
            'description' => 'Client fills out the form',
            'instructions' => [
                'client' => 'Please fill out the form'
            ],
            'actions' => ['fill-out-form'],
            'transitions' => [
                [
                    'transition' => ':success',
                ],
            ],
            'timeout' => 'P1DT2H',
            'display' => 'always',
        ]);

        $this->actionInstantiator->expects($this->once())->method('instantiate')
            ->will($this->returnCallback(function(AssocEntitySet $actionDefinitions, Process $process) use ($action) {
                $this->assertCount(1, $actionDefinitions);
                $this->assertSame($action, $actionDefinitions['fill-out-form']);

                $actionClone = clone $action;

                return new AssocEntitySet([$actionClone]);
            }));

        $current = $this->stateInstantiator->instantiate($state, $process);

        $this->assertInstanceOf(CurrentState::class, $current);
        $this->assertAttributeEquals('client-form', 'key', $current);
        $this->assertAttributeEquals('Fill out the form', 'title', $current);
        $this->assertAttributeEquals('Client fills out the form', 'description', $current);
        $this->assertAttributeEquals(['client' => 'Please fill out the form'], 'instructions', $current);

        $this->assertArrayHasKey('fill-out-form', $current->actions->getArrayCopy());
        $this->assertEquals($action, $current->actions['fill-out-form']);
        $this->assertNotSame($action, $current->actions['fill-out-form']);

        $this->assertAttributeCount(1, 'transitions', $current);
        $this->assertAttributeEquals(':success', 'transition', $current->transitions[0]);

        $this->assertAttributeInstanceOf(DateTimeInterface::class, 'due_date', $current);
        $this->assertEquals('2018-01-02T02:00:00+0000', $current->due_date->format(DATE_ISO8601));

        $this->assertAttributeEquals('always', 'display', $current);

        $this->assertSame($actor, $current->actor);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed to instantiate state 'initial' for process '00000000-0000-0000-0000-000000000000': some error
     */
    public function testInstantiateException()
    {
        $process = new Process();
        $process->id = '00000000-0000-0000-0000-000000000000';

        $state = new State();
        $state->key = 'initial';

        $this->enricher->expects($this->once())->method('applyTo')
            ->with($state, $this->identicalTo($process))
            ->willThrowException(new RuntimeException('some error'));

        $this->stateInstantiator->instantiate($state, $process);
    }


    protected function createProcess()
    {
        $scenario = new Scenario();

        $scenario->states['initial'] = new State();
        $scenario->states['initial']->actions = ['foo', 'bar'];
        $scenario->states['initial']->transitions[0] = StateTransition::fromData(['transition' => ':success']);
        $scenario->states['initial']->transitions[1] = StateTransition::fromData(['transition' => ':failed']);

        $scenario->actions['foo'] = Action::fromData(['title' => 'Foo']);
        $scenario->actions['bar'] = Action::fromData(['title' => 'Bar']);

        $current = new CurrentState();
        $current->key = 'initial';
        $current->actions['foo'] = new Action();
        $current->actions['bar'] = new Action();

        $process = new Process();
        $process->current = $current;
        $process->scenario = $scenario;

        return $process;
    }

    public function testRecalcActions()
    {
        $process = $this->createProcess();
        $scenario = $process->scenario;

        $this->actionInstantiator->expects($this->once())->method('instantiate')
            ->with($scenario->actions, $this->identicalTo($process))
            ->willReturn($scenario->actions);

        $this->stateInstantiator->recalcActions($process);
    }

    public function testRecalcTransitions()
    {
        $process = $this->createProcess();
        $scenario = $process->scenario;

        $this->enricher->expects($this->exactly(2))->method('applyTo')
            ->withConsecutive(
                [$scenario->states['initial']->transitions[0], $this->identicalTo($process)],
                [$scenario->states['initial']->transitions[1], $this->identicalTo($process)]
            );

        $this->stateInstantiator->recalcTransitions($process);
    }
}
