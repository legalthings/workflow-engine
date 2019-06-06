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
            ['!eval foo.bar == null', ['<eval>' => 'foo.bar == null']],
            ["!ref foo.bar == 'test' && foo.baz == 'rest'", ['<ref>' => "foo.bar == 'test' && foo.baz == 'rest'"]],
            [['<eval>' => 'foo.bar == null'], ['<eval>' => 'foo.bar == null']],
        ];
    }

    /**
     * Test 'fromData' method
     *
     * @dataProvider fromDataProvider
     */
    public function testFromData($data, $expected)
    {
        $result = DataInstruction::fromData($data);
        $vars = get_object_vars($result);

        $this->assertInstanceOf(DataInstruction::class, $result);
        $this->assertEquals($expected, $vars);
    }

    /**
     * Provide data for testing 'fromData' method, when exception is thrown
     *
     * @return array
     */
    public function fromDataExceptionProvider()
    {
        return [
            ['eval foo.bar == null'],
            ['!eval2 foo.bar == null'],
            ['!eval'],
            ['!eval ']
        ];
    }

    /**
     * Test 'fromData' method, when exception is thrown
     *
     * @dataProvider fromDataExceptionProvider
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid format for data instruction
     */
    public function testFromDataException($data)
    {
        DataInstruction::fromData($data);
    }

    /**
     * Provide data for testing '__toString' method
     *
     * @return array
     */
    public function toStringProvider()
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
     * Test '__toString' method
     *
     * @dataProvider toStringProvider
     */
    public function testToString($instruction, $expected)
    {
        $instruction->foo = 'bar';

        $result = (string)$instruction;

        $this->assertSame($expected, $result);
    }
}
