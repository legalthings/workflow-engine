<?php

use Jasny\DB\EntitySet;
use Jasny\ValidationResult;

/**
 * @covers State
 */
class StateTest extends \Codeception\Test\Unit
{
    /**
     * @var State
     **/
    protected $state;

    /**
     * Perform actions before each test case
     */
    public function _before()
    {
        $this->state = new State();
    }

    /**
     * Test 'cast' method
     */
    public function testCast()
    {
        $transitions = [
            $this->createMock(StateTransition::class),
            $this->createMock(StateTransition::class)
        ];

        $this->state->transitions = $transitions;

        $result = $this->state->cast();

        $this->assertSame($this->state, $result);
        $this->assertInstanceOf(EntitySet::class, $this->state->transitions);
        $this->assertCount(2, $this->state->transitions);
        $this->assertSame($transitions[0], $this->state->transitions[0]);
        $this->assertSame($transitions[1], $this->state->transitions[1]);
    }

    /**
     * Test 'validate' method
     */
    public function testValidate()
    {
        $this->state->display = 'foo'; // make property not valid

        $result = $this->state->validate();

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->succeeded()); // validation always succeeds, because it's stubed
    }

    /**
     * Provide data for testing 'isFinal' method
     *
     * @return array
     */
    public function isFinalProvider()
    {
        return [
            [[], true],
            [['foo'], false],
            [['foo', 'bar', 'baz'], false]
        ];
    }

    /**
     * Test 'isFinal' method
     *
     * @dataProvider isFinalProvider
     */
    public function testIsFinal($actions, $expected)
    {
        $this->state->actions = $actions;

        $result = $this->state->isFinal();

        $this->assertSame($expected, $result);
    }

    /**
     * Test 'toData' method
     */
    public function testToData()
    {
        $this->state->title = (new DataInstruction())->setValues(['foo' => 'bar']);
        $this->state->key = 'baz';

        $result = $this->state->toData();

        $expected = [
            'key' => 'baz',
            'display' => 'always',
            'title' => ['foo' => 'bar'],
            'instructions' => [],
            'transitions' => []
        ];

        $this->assertEquals($expected, $result);
    }
}
