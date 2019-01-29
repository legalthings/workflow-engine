<?php

/**
 * @covers DataPatcher
 */
class DataPatcherTest extends \Codeception\Test\Unit
{
    /**
     * @var DataPatcher
     */
    protected $patcher;

    public function _before()
    {
        $this->patcher = new DataPatcher();
    }

    public function setProvider()
    {
        return [
            [
                ['color' => 'blue'],
                'shape',
                'square',
                ['color' => 'blue', 'shape' => 'square'],
            ],
            [
                new ArrayObject(['color' => 'blue']),
                'shape',
                'square',
                new ArrayObject(['color' => 'blue', 'shape' => 'square']),
            ],
            [
                (object)['color' => 'blue'],
                'shape',
                'square',
                (object)['color' => 'blue', 'shape' => 'square'],
            ],
        ];
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet($subject, string $selector, $value, $expected)
    {
        $this->patcher->set($subject, $selector, $value);

        $this->assertEquals($expected, $subject);
    }

    public function testSetDeep()
    {
        $object = (object)[
            'color' => 'blue',
        ];

        $this->patcher->set($object, 'foo.numbers', ['I' => 'one', 'II' => 'two']);

        $expected = (object)[
            'color' => 'blue',
            'foo' => (object)[
                'numbers' => [
                    'I' => 'one',
                    'II' => 'two',
                ],
            ],
        ];

        $this->assertEquals($expected, $object);
    }


    public function patchProvider()
    {
        $input = [
            'color' => 'blue',
            'numbers' => ['I' => 'uno', 'II' => 'dos'],
        ];

        $expectedWithPatch = [
            'color' => 'blue',
            'numbers' => ['I' => 'uno', 'II' => 'two', 'III' => 'three'],
        ];

        $expectedWithoutPatch = [
            'color' => 'blue',
            'numbers' => ['II' => 'two', 'III' => 'three'],
        ];

        return [
            [$input, $expectedWithPatch, $expectedWithoutPatch],
            [(object)$input, (object)$expectedWithPatch, (object)$expectedWithoutPatch],
            [objectify($input), objectify($expectedWithPatch), (object)$expectedWithoutPatch],
            [
                ['color' => 'blue', 'numbers' => new ArrayObject($input['numbers'])],
                ['color' => 'blue', 'numbers' => new ArrayObject($expectedWithPatch['numbers'])],
                $expectedWithoutPatch,
            ]
        ];
    }

    /**
     * @dataProvider patchProvider
     */
    public function testPatch($subject, $expected)
    {
        $this->patcher->set($subject, 'numbers', ['II' => 'two', 'III' => 'three'], true);

        $this->assertEquals($expected, $subject);
    }

    /**
     * @dataProvider patchProvider
     */
    public function testNoPatch($subject, $_, $expected)
    {
        $this->patcher->set($subject, 'numbers', ['II' => 'two', 'III' => 'three'], false);

        $this->assertEquals($expected, $subject);
    }
}
