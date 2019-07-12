<?php

namespace JsonSchema\Validator\Loader;

use org\bovigo\vfs\vfsStream;

/**
 * @covers JsonSchema\Validator\Loader\FileSource
 */
class FileSourceTest extends \Codeception\Test\Unit
{
    /**
     * @var FileSource
     **/
    protected $loader;

    /**
     * Execute actions before each test case
     */
    public function _before()
    {
        $this->loader = new FileSource();
    }

    /**
     * Provide data for testing 'toLocalPath' method
     *
     * @return array
     */
    public function toLocalPathProvider()
    {
        return [
            ['invalid_url', null],
            ['https://specs.livecontracts.io/v0.1.0/foo/schema.json#', 'config/schemas/v0.1.0/foo/schema.json'],
        ];
    }

    /**
     * Test 'toLocalPath' method
     *
     * @dataProvider toLocalPathProvider
     */
    public function testToLocalPath($url, $expected)
    {
        $result = $this->loader->toLocalPath($url);

        $this->assertSame($expected, $result);
    }

    /**
     * Test 'fetch' method
     */
    public function testFetch()
    {
        $root = vfsStream::setup('root', null, ['foo.json' => '{"foo": "bar"}']);

        $result = $this->loader->fetch($root->url() . '/foo.json');

        $expected = (object)['foo' => 'bar'];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test 'fetch' method, if file does not exist
     */
    public function testFetchFileNotExists()
    {
        $root = vfsStream::setup('root', null, ['foo.json' => '{"foo": "bar"}']);

        $result = $this->loader->fetch($root->url() . '/bar.json');

        $this->assertSame(null, $result);        
    }

    /**
     * Provide data for testing 'fetch' method
     *
     * @return array
     */
    public function fetchJsonErrorProvider()
    {
        return [
            ['{"foo": "bar"'],
            ['']
        ];
    }

    /**
     * Test 'fetch' method, if there was an error decoding json
     *
     * @dataProvider fetchJsonErrorProvider
     */
    public function testFetchJsonError($content)
    {
        $root = vfsStream::setup('root', null, ['foo.json' => $content]);

        $result = @$this->loader->fetch($root->url() . '/foo.json');
        $error = error_get_last();

        $this->assertSame(null, $result);        
        $this->assertSame('Invalid JSON Schema in path vfs://root/foo.json: Syntax error', $error['message']);
        $this->assertSame(E_USER_WARNING, $error['type']);
    }    

    /**
     * Test 'fetch' method, if there was an error while opening file
     */
    public function testFetchErrorOpenFile()
    {
        $root = vfsStream::setup('root', null, ['foo.json' => '{"foo": "bar"}']);

        $root->getChild('foo.json')->chmod(0000);

        $result = @$this->loader->fetch($root->url() . '/foo.json');
        $error = error_get_last();

        $this->assertSame(null, $result);        
        $this->assertSame('Error obtaining schema from path: vfs://root/foo.json', $error['message']);
        $this->assertSame(E_USER_WARNING, $error['type']);
    }
}
