<?php

use JmesPath\Env as JmesPath;

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
        $jsonpath = JmesPath::createRuntime();
        $this->patcher = new DataPatcher($jsonpath);
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

    /**
     * @dataProvider setProvider
     */
    public function testSetOverwrite()
    {
        $subject = ['color' => 'blue', 'numbers' => ['I' => 'one']];

        $this->patcher->set($subject, 'color', ['r' => 0, 'g' => 0, 'b' => 160], true);
        $this->patcher->set($subject, 'numbers', 42, true);

        $this->assertEquals(['color' => ['r' => 0, 'g' => 0, 'b' => 160], 'numbers' => 42], $subject);
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

    public function testSetRemove()
    {
        $object = (object)[
            'color' => 'blue',
            'foo' => (object)[
                'numbers' => [
                    'I' => 'one',
                    'II' => 'two',
                ],
            ],
        ];

        $this->patcher->set($object, 'foo.numbers', null);

        $expected = (object)[
            'color' => 'blue',
            'foo' => (object)[],
        ];

        $this->assertEquals($expected, $object);
    }


    public function addProvider()
    {
        return [
            [[], ['added']],
            [['foo'], ['foo', 'added']],
            [new ArrayObject(), new ArrayObject(['added'])],
            [new ArrayObject(['foo']), new ArrayObject(['foo', 'added'])],
        ];
    }

    /**
     * @dataProvider addProvider
     */
    public function testSetAdd($items, $expectedItems)
    {
        $subject = ['items' => $items];

        $this->patcher->set($subject, 'items', 'added', true);

        $this->assertEquals(['items' => $expectedItems], $subject);
    }

    /**
     * @dataProvider addProvider
     */
    public function testSetNoAdd($items)
    {
        $subject = ['items' => $items];

        $this->patcher->set($subject, 'items', 'added', false);

        $this->assertEquals(['items' => 'added'], $subject);
    }


    public function mergeProvider()
    {
        $input = [
            'color' => 'blue',
            'numbers' => ['I' => 'uno', 'II' => 'dos', 'IV' => 'quatro'],
        ];

        $expectedWithMerge = [
            'color' => 'blue',
            'numbers' => ['I' => 'uno', 'II' => 'two', 'III' => 'three'],
        ];

        $expectedWithoutMerge = [
            'color' => 'blue',
            'numbers' => ['II' => 'two', 'III' => 'three', 'IV' => null],
        ];

        return [
            [$input, $expectedWithMerge, $expectedWithoutMerge],
            [(object)$input, (object)$expectedWithMerge, (object)$expectedWithoutMerge],
            [objectify($input), objectify($expectedWithMerge), (object)$expectedWithoutMerge],
            [
                ['color' => 'blue', 'numbers' => new ArrayObject($input['numbers'])],
                ['color' => 'blue', 'numbers' => new ArrayObject($expectedWithMerge['numbers'])],
                $expectedWithoutMerge,
            ]
        ];
    }

    /**
     * @dataProvider mergeProvider
     */
    public function testMerge($subject, $expected)
    {
        $this->patcher->set($subject, 'numbers', ['II' => 'two', 'III' => 'three', 'IV' => null], true);

        $this->assertEquals($expected, $subject);
    }

    /**
     * @dataProvider mergeProvider
     */
    public function testNoMerge($subject, $_, $expected)
    {
        $this->patcher->set($subject, 'numbers', ['II' => 'two', 'III' => 'three', 'IV' => null], false);

        $this->assertEquals($expected, $subject);
    }

    /**
     * @dataProvider mergeProvider
     */
    public function testMergeRecursive($sub, $expectedSub)
    {
        $subject = [
            'sub' => $sub,
            'foo' => 'bar',
        ];

        $this->patcher->set($subject, 'sub', ['numbers' => ['II' => 'two', 'III' => 'three', 'IV' => null]], true);

        $expected = [
            'sub' => $expectedSub,
            'foo' => 'bar',
        ];

        $this->assertEquals($expected, $subject);
    }


    protected function createProcess(): Process
    {
        // Only actors and assets are allowed to be set.
        // Adding actors and assets is possible, but shouldn't be done. Should throw exception or caught by validation.
        // Note that actors and assets are associative entity sets. Items can be references via the key.

        /** @var Process $process */
        $process = Process::fromData([
            'id' => '00000000-0000-0000-0000-000000000000',
            'actors' => [
                [
                    'key' => 'client',
                    'title' => 'Client',
                    'name' => null,
                ],
                [
                    'key' => 'manager',
                    'title' => 'Manager',
                    'name' => 'Jane Black',
                    'organization' => [
                        'name' => 'Acme Corp',
                    ]
                ],
            ],
            'assets' => [
                [
                    'key' => 'document',
                    'title' => 'Document',
                    'id' => '0001',
                    'content' => 'Foo bar'
                ],
                [
                    'key' => 'attachments',
                    'title' => 'attachments',
                    'urls' => []
                ],
                [
                    'key' => 'data',
                    'I' => 'uno',
                ],
            ],
            'current' => ['key' => 'initial'],
        ]);

        return $process;
    }

    public function testSetProcessActorInfo()
    {
        $process = $this->createProcess();

        $this->patcher->set($process, 'actors.client.name', 'John Doe', true);

        $this->assertEquals('John Doe', $process->actors['client']->name);

        // Assert nothing else changed
        $expected = $this->createProcess();
        $expected->actors['client']->name = 'John Doe';
        $this->assertEquals($expected, $process);
    }

    public function testSetProcessActors()
    {
        $process = $this->createProcess();

        $value = [
            'client' => [
                'name' => 'John Doe',
            ],
            'manager' => [
                'organization' => null,
                'name' => 'Foop Noop',
            ]
        ];

        $this->patcher->set($process, 'actors', $value, true);

        // Assert nothing else changed
        $expected = $this->createProcess();
        $expected->actors['client']->name = 'John Doe';
        $expected->actors['manager']->name = 'Foop Noop';
        unset($expected->actors['manager']->organization);

        $this->assertEquals($expected, $process);
    }

    public function testSetProcessDataAsset()
    {
        $process = $this->createProcess();

        $value = [
            'I' => 'one',
            'II' => 'two',
        ];

        $this->patcher->set($process, 'assets.data', $value);

        $expected = [
            'schema' => null,
            'key' => 'data',
            'I' => 'one',
            'II' => 'two',
        ];
        $this->assertInstanceOf(Asset::class, $process->assets['data']);
        $this->assertEquals($expected, $process->assets['data']->getValues());
    }


    public function projectProvider()
    {
        $subject = [
            'color' => 'blue',
            'foo' => (object)[
                'numbers' => [
                    'I' => 'one',
                    'II' => 'two',
                ],
            ],
        ];

        return [
            [$subject],
            [(object)$subject],
        ];
    }

    /**
     * @dataProvider projectProvider
     */
    public function testProject($subject)
    {
        $result = $this->patcher->project($subject, '{choice: color, numbers: foo.numbers.*, bar: bar}');

        $this->assertEquals(['choice' => 'blue', 'numbers' => ['one', 'two'], 'bar' => null], $result);
    }

    public function testProjectProcess()
    {
        $process = $this->createProcess();

        $result = $this->patcher->project(
            $process,
            '{current: current.key, manager: actors.manager.name, content: assets.document.content}'
        );

        $this->assertEquals(['current' => 'initial', 'manager' => 'Jane Black', 'content' => 'Foo bar'], $result);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage JMESPath projection failed: Syntax error at character 4
     *   not valid
     *       ^
     *   Did not reach the end of the token stream
     */
    public function testProjectException()
    {
        $this->patcher->project(['foo' => 'bar'], 'not valid');
    }
}
