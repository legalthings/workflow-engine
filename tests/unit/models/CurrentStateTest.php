<?php

use Jasny\ValidationResult;
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
        $this->assertAttributeEquals(StateTransition::class, 'entityClass', $transitions);

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

    /**
     * Provide data for testing 'getTransition' method
     *
     * @return array
     */
    public function getTransitionProvider()
    {
        $transitions = $this->getTransitions();

        $transitions2 = $transitions;
        unset($transitions2[3]);

        return [
            [$transitions, 'bar', 'error', $transitions[0]],
            [$transitions, 'not_set', 'test', $transitions[1]],
            [$transitions, 'zoo', 'not_set', $transitions[2]],
            [$transitions, 'not_set', 'not_set_too', $transitions[3]],
            [$transitions, 'do', 'ok', $transitions[3]],
            [$transitions, 'foo', 'ok', $transitions[3]],
            [$transitions, 'baz', 'other', $transitions[3]],
            [$transitions2, 'do', 'ok', $transitions2[4]],
            [$transitions2, 'foo', 'ok', null],
            [$transitions2, 'baz', 'other', null],
        ];
    }

    /**
     * Test 'getTransition' method
     *
     * @dataProvider getTransitionProvider
     */
    public function testGetTransition($transitions, $action, $response, $expected)
    {
        $state = new CurrentState();        
        $state->transitions = $transitions;

        $result = $state->getTransition($action, $response);

        $this->assertSame($expected, $result);
    }

    /**
     * Get mocked transitions
     *
     * @return array
     */
    protected function getTransitions()
    {
        $transitions = [
            $this->createMock(StateTransition::class),
            $this->createMock(StateTransition::class),
            $this->createMock(StateTransition::class),
            $this->createMock(StateTransition::class),
            $this->createMock(StateTransition::class),
            $this->createMock(StateTransition::class),
            $this->createMock(StateTransition::class)
        ];

        $transitions[0]->action = 'bar';
        $transitions[0]->response = 'error';
        $transitions[0]->condition = true;

        $transitions[1]->response = 'test';
        $transitions[1]->condition = 'bar';

        $transitions[2]->action = 'zoo';
        $transitions[2]->condition = 1;

        $transitions[4]->action = 'do';
        $transitions[4]->response = 'ok';
        $transitions[4]->condition = true;

        $transitions[5]->action = 'foo';
        $transitions[5]->response = 'ok';
        $transitions[5]->condition = false;

        $transitions[6]->action = 'baz';
        $transitions[6]->response = 'other';
        $transitions[6]->condition = 0;

        return $transitions;
    }
}
