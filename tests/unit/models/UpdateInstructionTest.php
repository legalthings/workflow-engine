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

    /**
     * Test 'cast' method
     */
    public function testCast()
    {
        $instruction = new UpdateInstruction();
        $instruction->data = [
            'foo' => 'bar', 
            'zoo' => ['zoos' => 'boos']
        ];

        $expected = (object)[
            'foo' => 'bar', 
            'zoo' => (object)['zoos' => 'boos']
        ];

        $result = $instruction->cast();

        $this->assertSame($instruction, $result);
        $this->assertEquals($expected, $result->data);
    }

    /**
     * Provide data for testing 'fromData' method
     *
     * @return array
     */
    public function fromDataProvider()
    {
        return [
            ['foo'],
            [['select' => 'foo']],
            [(object)['select' => 'foo']],
        ];
    }

    /**
     * Test 'fromData' method
     *
     * @dataProvider fromDataProvider
     */
    public function testFromData($data)
    {        
        $result = UpdateInstruction::fromData($data);

        $this->assertInstanceOf(UpdateInstruction::class, $result);
        $this->assertSame('foo', $result->select);
        $this->assertSame(true, $result->patch);
        $this->assertSame(null, $result->data);
        $this->assertSame(null, $result->projection);
    }
}
