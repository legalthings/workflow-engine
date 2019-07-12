<?php

namespace JsonSchema\Validator;

use JsonSchema\Validator;
use JsonSchema\Validator\Loader\FileSource;
use JsonSchema\Constraints\Constraint;

/**
 * @covers JsonSchema\Validator\Repository
 */
class RepositoryTest extends \Codeception\Test\Unit
{
    /**
     * Test 'get' method
     */
    public function testGet()
    {
        $url = 'http://schema.foo.url';
        $path = 'some/local/path';
        $expected = (object)[
            'bar' => [
                (object)['key_bar' => 'value_bar'],
                null,
                (object)[
                    'baz' => 'test',
                    'groo' => (object)[
                        'key_groo' => 'value_groo',
                        'alpha' => (object)['key_alpha' => 'value_alpha']
                    ],
                    'bar2' => (object)['key_bar' => 'value_bar'],
                    'zoo2' => null
                ]
            ]
        ];

        $schemas = $this->getExpectedSchemas();

        $loader = $this->createMock(FileSource::class);
        $loaders = ['file' => $loader];

        $loader->expects($this->exactly(5))->method('toLocalPath')
            ->withConsecutive(
                ['http://schema.foo.url'], 
                ['http://schema.bar.url'],
                ['http://schema.zoo.url'],
                ['http://schema.groo.url'],
                ['http://schema.alpha.url']
            )->willReturnOnConsecutiveCalls('path/foo', 'path/bar', 'path/zoo', 'path/groo', 'path/alpha');
        $loader->expects($this->exactly(5))->method('fetch')
            ->withConsecutive(
                ['path/foo'], 
                ['path/bar'], 
                ['path/zoo'], 
                ['path/groo'], 
                ['path/alpha']
            )->willReturnOnConsecutiveCalls($schemas['foo'], $schemas['bar'], $schemas['zoo'], $schemas['groo'], $schemas['alpha']);

        $repository = new Repository($loaders);
        $result = $repository->get($url);

        $this->assertEquals($expected, $result);
    }

    /**
     * Get expected schemas obtained by service
     *
     * @return array
     */
    protected function getExpectedSchemas()
    {
        return [
            'foo' => (object)[
                'bar' => [
                    ['$ref' => 'http://schema.bar.url'],
                    (object)['$ref' => 'http://schema.zoo.url'],
                    (object)[
                        'baz' => 'test',
                        'groo' => [
                            'key' => 'value',
                            '$ref' => 'http://schema.groo.url'
                        ],
                        'bar2' => (object)['$ref' => 'http://schema.bar.url'], //repeat to check if cache is used
                        'zoo2' => (object)['$ref' => 'http://schema.zoo.url'] //repeat to check if cache is used
                    ]
                ]
            ],
            'bar' => (object)['key_bar' => 'value_bar'],
            'zoo' => null,
            'groo' => (object)[
                'key_groo' => 'value_groo',
                'alpha' => ['$ref' => 'http://schema.alpha.url']
            ],
            'alpha' => (object)['key_alpha' => 'value_alpha'],
        ];
    }

    /**
     * Test 'get' method, if loader is not set
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Json schema file loader is not set
     */
    public function testGetNoLoader()
    {
        $repository = new Repository([]);
        $repository->get('foo');
    }
}
