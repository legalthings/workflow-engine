<?php

use Jasny\DB\EntitySet;

class FunctionsTest extends \Codeception\Test\Unit
{
    /**
     * Provide data for testing 'is_schema_link_valid' function
     *
     * @return array
     */
    public function isSchemaLinkValidProvider()
    {
        return [
            ['foo', 'foo', false],
            ['https://specs.livecontracts.io/0.2.0/identity/schema.json#', 'identity', false],
            ['https://foo/v0.2.0/identity/schema.json#', 'identity', false],
            ['https://specs.livecontracts.io/identity/schema.json#', 'identity', false],
            ['http://specs.livecontracts.io/v0.2.0/identity/schema.json#', 'identity', false],
            ['specs.livecontracts.io/v0.2.0/identity/schema.json#', 'identity', false],
            ['https://specs.livecontracts.io/v0.2.0/identity/schema.json#', 'process', false],
            ['https://specs.livecontracts.io/v0.2.0a/identity/schema.json#', 'identity', false],
            ['https://specs.livecontracts.io/v0.2.0/identity/schema.json#', 'identity', true],
            ['https://specs.livecontracts.io/v10.465.3/identity/schema.json#', 'identity', true],
        ];
    }

    /**
     * Test 'is_schema_link_valid' function
     *
     * @dataProvider isSchemaLinkValidProvider
     */
    public function testIsSchemaLinkValid($schema, $type, $expected)
    {
        $result = is_schema_link_valid($schema, $type);

        $this->assertSame($expected, $result);
    }

    /**
     * Provide data for testing 'array_rename_key' function
     *
     * @return array
     */
    public function arrayRenameKeyProvider()
    {
        return [
            [['foo' => 'bar', 'test' => 'rest'], 'foo', 'baz', ['baz' => 'bar', 'test' => 'rest']],
            [['foo' => 'bar', 'test' => 'rest'], 'baz', 'zoo', ['foo' => 'bar', 'test' => 'rest']],
            [['bar', 'baz'], 1, 'foo', ['bar', 'foo' => 'baz']],
            [['bar', 'baz'], 2, 'foo', ['bar', 'baz']],
        ];
    }

    /**
     * Test 'array_rename_key' function
     *
     * @dataProvider arrayRenameKeyProvider
     */
    public function testArrayRenameKey($array, $from, $to, $expected)
    {
        $result = array_rename_key($array, $from, $to);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test 'object_rename_key' function
     *
     * @dataProvider arrayRenameKeyProvider
     */
    public function testObjectRenameKey($array, $from, $to, $expected)
    {
        $result = object_rename_key((object)$array, $from, $to);

        $this->assertEquals((object)$expected, $result);
    }

    /**
     * Test 'object_copy_properties' function
     */
    public function testObjectCopyProperties()
    {
        $from = new class() {
            public $foo = 'foo_value';
            public $bar = 'bar_value';
            public $zoo = ['zoo' => 'baz'];
            protected $boom = 'bam';
            private $booh = 'bah';
        };

        $to = new class() {
            public $foo = null;
            public $zoo = 'zoo_string';
            public $test = 'rest';
            protected $boom = null;
            private $booh = 'bah_2';
        };

        $from->zoo = (object)$from->zoo;

        $result = object_copy_properties($from, $to);

        $this->assertSame('foo_value', $to->foo);
        $this->assertFalse(isset($to->bar));

        $this->assertEquals((object)['zoo' => 'baz'], $from->zoo);
        $this->assertEquals((object)['zoo' => 'baz'], $to->zoo);
        $this->assertNotSame($from->zoo, $to->zoo);

        $this->assertAttributeSame(null, 'boom', $to);
        $this->assertAttributeSame('bah_2', 'booh', $to);
    }

    /**
     * Test 'std_object_only_with' method
     */
    public function testStdObjectOnlyWith()
    {
        $object = (object)[
            'foo' => 'bar', 
            'zoo' => 'baz',
            'test' => 'rest',
            'boom' => 'bam'
        ];

        $result = std_object_only_with($object, ['foo', 'test']);

        $this->assertEquals((object)['foo' => 'bar', 'test' => 'rest'], $result);
    }

    /**
     * Test 'get_dynamic_properties' function
     */
    public function testGetDynamicProperties()
    {
        $object = new class() {
            public $foo = 'foo_value';
            public $bar = 'bar_value';
            protected $zoo = 'zoo_value';
            private $baz = 'baz_value';
        };

        $object->teta = 'teta_value';

        $result = get_dynamic_properties($object);
        $expected = ['teta'];

        $this->assertEquals($expected, $result);
    }
}
