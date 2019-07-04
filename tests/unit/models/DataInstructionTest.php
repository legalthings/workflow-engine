<?php

use Jasny\ValidationResult;

/**
 * @covers DataInstruction
 */
class DataInstructionTest extends \Codeception\Test\Unit
{
    /**
     * Provide data for testing 'fromData' method
     *
     * @return array
     */
    public function fromDataProvider()
    {
        return [
            [['<eval>' => 'foo.bar == null']],
            [(object)['<eval>' => 'foo.bar == null']],
        ];
    }

    /**
     * Test 'fromData' method
     *
     * @dataProvider fromDataProvider
     */
    public function testFromData($data)
    {
        $result = DataInstruction::fromData($data);
        $vars = get_object_vars($result);

        $expected = ['<eval>' => 'foo.bar == null'];

        $this->assertInstanceOf(DataInstruction::class, $result);
        $this->assertEquals($expected, $vars);
    }

    /**
     * Provide data for testing 'getInstruction' method
     *
     * @return array
     */
    public function getInstructionProvider()
    {
        $instruction = new DataInstruction();
        $instruction2 = new DataInstruction();

        $instruction->{'<zoo>'} = 'baz';

        return [
            [$instruction, 'baz'],
            [$instruction2, ''],
        ];
    }

    /**
     * Test 'getInstruction' method
     *
     * @dataProvider getInstructionProvider
     */
    public function testGetInstruction($instruction, $expected)
    {
        $instruction->foo = 'bar';

        $result = $instruction->getInstruction();

        $this->assertSame($expected, $result);
    }
}
