<?php

use Jasny\ValidationResult;

/**
 * @covers JsonSchema
 */
class JsonSchemaTest extends \Codeception\Test\Unit
{
    /**
     * Provide data for testing '__construct' method
     *
     * @return array
     */
    public function constructProvider()
    {
        $data = [
            '$id' => 'foo',
            '$comment' => 'bar',
            'title' => 'Some title',
            'properties' => ['zoo']
        ];

        return [
            [$data],
            [(object)$data]
        ];
    }

    /**
     * Test '__construct' method
     *
     * @dataProvider constructProvider
     */
    public function testConstruct($data)
    {        
        $schema = new JsonSchema($data);

        $this->assertSame('foo', $schema->id);
        $this->assertSame('bar', $schema->comment);
        $this->assertSame('Some title', $schema->title);
        $this->assertEquals(['zoo'], $schema->properties);

        $this->assertFalse(isset($schema->{'$id'}));
        $this->assertFalse(isset($schema->{'$comment'}));
    }

    /**
     * Test 'build' method for objects
     */
    public function testBuildObject()
    {
        $object = $this->getTestObject();
        $result = $object->build();

        $expected = (object)[
            'foo' => (object)[
                'nested_foo' => (object)[
                    'deep_foo' => true
                ]
            ],
            'bar' => [],
            'zoo' => 12,
            'baz' => '12'
        ];

        $this->assertEquals($expected, $result);   
        $this->assertSame(true, $result->foo->nested_foo->deep_foo);
        $this->assertSame(12., $result->zoo);
        $this->assertSame('12', $result->baz);
    }

    /**
     * Test 'build' method for array
     */
    public function testBuildArray()
    {
        $object = new class([]) extends JsonSchema {
            public $type = 'array';
            public $properties = [
                'foo' => [
                    'type' => 'number',
                    'default' => 13
                ]
            ];
        };

        $result = $object->build();
        $this->assertEquals([], $result);
    }

    /**
     * Provide data for testing 'build' method with default values
     *
     * @return array
     */
    public function buildDefaultProvider()
    {
        return [
            ['float', '12', 12.],
            ['double', '12', 12.],
            ['number', '12', 12.],
            ['integer', '12', 12],
            ['int', '12', 12],
            ['number', '12qwe', 12.],
            ['boolean', '12qwe', true],
            ['bool', '12qwe', true],
            ['bool', '', false],
            ['string', 12., '12'],
        ];
    }

    /**
     * Test 'build' method with default value
     *
     * @dataProvider buildDefaultProvider
     */
    public function testBuildDefault($type, $default, $expected)
    {
        $object = new class(compact('type', 'default')) extends JsonSchema {};        
        $result = $object->build();

        $this->assertSame($expected, $result);
    }

    /**
     * Test 'typeCast' method
     */
    public function testTypeCast()
    {
        $object = $this->getTestObject();
        $target = [
            'foo' => [
                'nested_foo' => [
                    'deep_foo' => 1
                ],
                'more_foo' => 'test'
            ],
            'bar' => 'bar_string',
            'zoo' => '12asd'
        ];

        $expected = (object)[
            'foo' => (object)[
                'nested_foo' => (object)[
                    'deep_foo' => true
                ],
                'more_foo' => 'test'
            ],
            'bar' => ['bar_string'],
            'zoo' => 12.,
            'baz' => null
        ];

        $result = $object->typeCast($target);

        $this->assertEquals($expected, $result);
        $this->assertSame($expected->foo->nested_foo->deep_foo, $result->foo->nested_foo->deep_foo);
        $this->assertSame('test', $result->foo->more_foo);
        $this->assertSame('bar_string', $result->bar[0]);
        $this->assertSame(12., $result->zoo);
        $this->assertSame(null, $result->baz);
    }

    /**
     * Test 'fromData' method
     */
    public function testFromData()
    {
        $data = ['$id' => 'foo', 'test' => 'bar'];
        $result = JsonSchema::fromData($data);

        $this->assertInstanceOf(JsonSchema::class, $result);
        $this->assertSame('foo', $result->id);
        $this->assertSame('bar', $result->test);
        $this->assertFalse(isset($result->{'$id'}));
    }

    /**
     * Test 'toData' method
     */
    public function testToData()
    {
        $object = $this->getSimpleTestObject();
        $result = $object->toData();

        $expected = [
            'schema' => 'http://json-schema.org/draft-07/schema#',
            'id' => 'foo',
            'title' => 'Foo title',
            'comment' => 'Foo comment',
            'properties' => ['bar'],
            'bar' => 'bars'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test 'jsonSerialize' method
     */
    public function testJsonSerialize()
    {
        $object = $this->getSimpleTestObject();
        $result = $object->jsonSerialize();

        $expected = (object)[
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            '$id' => 'foo',
            '$comment' => 'Foo comment',
            'title' => 'Foo title',
            'properties' => ['bar'],
            'bar' => 'bars'  
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Get simple test object
     *
     * @return object
     */
    protected function getSimpleTestObject()
    {
        return new class([]) extends JsonSchema {
            public $id = 'foo';
            public $title = 'Foo title';
            public $comment = 'Foo comment';
            public $properties = ['bar'];
            public $bar = 'bars';
            public $nullBar = null;
            protected $zoo = 'zoos';
            private $baz = 'bazes';
        };
    }

    /**
     * Get object for building
     *
     * @return object
     */
    protected function getTestObject()
    {
        return new class([]) extends JsonSchema {
            public $type = 'object';
            public $id = 'some_id';
            public $properties = [
                'foo' => [
                    'type' => 'object',
                    '$id' => 'foo_object',
                    'properties' => [
                        'nested_foo' => [
                            'type' => 'object',
                            '$comment' => 'Very nested object',
                            'properties' => [
                                'deep_foo' => [
                                    'type' => 'boolean',
                                    'default' => true
                                ]
                            ]
                        ]
                    ]
                ],
                'bar' => [
                    'type' => 'array',
                    '$id' => 'not_used_id'
                ],
                'zoo' => [
                    'type' => 'number',
                    'default' => '12'
                ],
                'baz' => [
                    'type' => 'string',
                    'default' => 12
                ]
            ];
        };
    }
}
