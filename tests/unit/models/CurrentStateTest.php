<?php

use Jasny\DB\EntitySet;
use Carbon\CarbonImmutable;

/**
 * @covers CurrentState
 */
class CurrentStateTest extends \Codeception\Test\Unit
{
    /**
     * Test 'cast' method
     */
    public function testCast()
    {
        $date = new DateTime();
        $transition1 = $this->createMock(StateTransition::class);
        $transition2 = $this->createMock(StateTransition::class);

        $state = new CurrentState();
        $state->transitions = [$transition1, $transition2];
        $state->due_date = $date;

        $state->cast();

        $transitions = $state->transitions;

        $this->assertInstanceOf(EntitySet::class, $transitions);
        $this->assertEquals(StateTransition::class, $transitions->getEntityClass());

        $this->assertCount(2, $transitions);        
        $this->assertSame($transition1, $transitions[0]);
        $this->assertSame($transition2, $transitions[1]);

        $this->assertInstanceOf(DateTimeImmutable::class, $state->due_date);
        $this->assertSame($date->getTimestamp(), $state->due_date->getTimestamp());
    }

    /**
     * Test 'toData' method
     */
    public function testToData()
    {
        $date = '2018-02-16 00:00:00';

        $state = new CurrentState();
        $state->due_date = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date);

        $result = $state->toData();

        $this->assertInstanceOf(DateTime::class, $result['due_date']);
        $this->assertSame($date, $result['due_date']->format('Y-m-d H:i:s'));
    }

    /**
     * Provide data for testing 'getDefaultAction' method
     *
     * @return array
     */
    public function getDefaultActionProvider()
    {
        $actions = [
            $this->createMock(Action::class),
            $this->createMock(Action::class),
            $this->createMock(Action::class)
        ];

        return [
            [$actions, true, $actions[1]],
            [$actions, 1, $actions[1]],
            [$actions, 'foo', $actions[1]],
            [$actions, false, null],
            [$actions, 0, null],
            [$actions, '', null],
            [$actions, null, null]
        ];
    }

    /**
     * Test 'getDefaultAction' method
     *
     * @dataProvider getDefaultActionProvider
     */
    public function testGetDefaultAction($actions, $trueCondition, $expected)
    {
        $state = new CurrentState();        

        $actions[0]->condition = false;
        $actions[1]->condition = $trueCondition;
        $actions[2]->condition = false;

        $state->actions = $actions;

        $result = $state->getDefaultAction();

        $this->assertSame($expected, $result);
    }

    public function testGetTransition()
    {
        $response = $this->createMock(Response::class);

        $currentState = new CurrentState();

        $currentState->transitions[0] = $this->createMock(StateTransition::class);
        $currentState->transitions[0]->expects($this->once())->method('appliesTo')
            ->with($this->identicalTo($response))
            ->willReturn(false);
        $currentState->transitions[0]->expects($this->never())->method('meetsCondition');

        $currentState->transitions[1] = $this->createMock(StateTransition::class);
        $currentState->transitions[1]->expects($this->once())->method('appliesTo')
            ->with($this->identicalTo($response))
            ->willReturn(true);
        $currentState->transitions[1]->expects($this->once())->method('meetsCondition')
            ->willReturn(false);

        $currentState->transitions[2] = $this->createMock(StateTransition::class);
        $currentState->transitions[2]->expects($this->once())->method('appliesTo')
            ->with($this->identicalTo($response))
            ->willReturn(true);
        $currentState->transitions[2]->expects($this->once())->method('meetsCondition')
            ->willReturn(true);

        $currentState->transitions[3] = $this->createMock(StateTransition::class);
        $currentState->transitions[3]->expects($this->never())->method('appliesTo');
        $currentState->transitions[3]->expects($this->never())->method('meetsCondition');

        $transition = $currentState->getTransition($response);

        $this->assertSame($currentState->transitions[2], $transition);
    }
}
