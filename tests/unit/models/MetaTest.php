<?php

/**
 * @covers Meta
 */
class MetaTest extends \Codeception\Test\Unit
{
    /**
     * Test 'toData' method
     */
    public function testToData()
    {
        $meta = new class() extends Meta {
            public $foo = 'foos';
            public $bar = 'bars';
        };

        $result = $meta->toData();

        $expected = (object)[
            'foo' => 'foos',
            'bar' => 'bars'
        ];

        $this->assertEquals($expected, $result);
    }
}
