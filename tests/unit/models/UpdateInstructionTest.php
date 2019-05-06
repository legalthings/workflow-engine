<?php

use Jasny\DB\EntitySet;
use Jasny\ValidationResult;

/**
 * @covers UpdateInstruction
 */
class UpdateInstructionTest extends \Codeception\Test\Unit
{
    /**
     * Provide data for testing 'validate' method
     *
     * @return array
     */
    public function validateProvider()
    {
        return [
            ['[1', true],
            ['[1]', false]
        ];
    }

    /**
     * Test 'validate' method
     *
     * @dataProvider validateProvider
     */
    public function testValidate($projection, $hasError)
    {
        $instruction = new UpdateInstruction();
        $instruction->projection = $projection;

        $validation = $instruction->validate();
        $errors = $validation->getErrors();

        if ($hasError) {
            $this->assertCount(1, $errors);
            $this->assertTrue(strpos($errors[0], 'jmespath projection has a syntax error: ') === 0);
        } else {
            $this->assertCount(0, $errors);
        }
    }
}
