<?php

use Jasny\DB\EntitySet;
use Jasny\ValidationResult;

/**
 * @covers StateTransition
 */
class StateTransitionTest extends \Codeception\Test\Unit
{
    /**
     * Test 'validate' method
     */
    public function testValidate()
    {
        $transition = new StateTransition();
        $result = $transition->validate();

        $this->assertTrue($result->succeeded()); // validation always succeeds
    }
}
