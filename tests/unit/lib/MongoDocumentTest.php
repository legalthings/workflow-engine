<?php

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamInterface;
use Jasny\ValidationResult;
use Jasny\ValidationException;

/**
 * @covers MongoDocument
 */
class MongoDocumentTest extends \Codeception\Test\Unit
{
    /**
     * Test 'decodeUnicodeChars' methodmethodName
     */
    public function testDecodeUnicodeChars()
    {
        $value = (object)[
            '\\u00c4' => 'foo',
            't\\u00cbst' => [
                'bar' => [
                    '\\u00cf' => 'valu\\u00eb'
                ]
            ]
        ];

        $expected = (object)[
            'Ä' => 'foo',
            'tËst' => (object)[
                'bar' => (object)[
                    'Ï' => 'valu\\u00eb'
                ]
            ]
        ];

        $result = MongoDocument::decodeUnicodeChars($value);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test 'fromData' method
     */
    public function testFromData()
    {
        $source = new class() extends MongoDocument
        {
            public $foo;
        };

        $class = get_class($source);
        $data = [
            'foo' => [
                '\\u00c4' => 'foo',
                't\\u00cbst' => [
                    'bar' => [
                        '\\u00cf' => 'valu\\u00eb'
                    ]
                ]
            ]
        ];

        $expected = (object)[
            'Ä' => 'foo',
            'tËst' => (object)[
                'bar' => (object)[
                    'Ï' => 'valu\\u00eb'
                ]
            ]
        ];

        $result = $class::fromData($data);
        
        $this->assertInstanceOf($class, $result);
        $this->assertEquals($expected, $result->foo);
    }

    /**
     * Test 'toData' method
     */
    public function testToData()
    {
        $source = new class() extends MongoDocument
        {
            public $foo = 'foo_value';
            public $bar = 'bar_value';
            protected $zoo = 'zoo_value';
            private $baz = 'baz_value';
        };

        $source->test = 'rest';

        $result = $source->toData();

        $expected = [
            'foo' => 'foo_value',
            'bar' => 'bar_value',
            'test' => 'rest'
        ];

        $this->assertEquals($expected, $result);
    }
}
