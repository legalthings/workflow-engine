<?php

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamInterface;
use Jasny\ValidationResult;
use Jasny\ValidationException;

/**
 * @covers BasicEntity
 */
class BasicEntityTest extends \Codeception\Test\Unit
{
    use Jasny\TestHelper;

    /**
     * Test 'setValues' method
     */
    public function testSetValues()
    {
        $entity = $this->getEntity();
        $entity->setValues(['$schema' => 'foo', 'data' => ['bar' => 'baz']]);

        $this->assertSame('foo', $entity->schema);
        $this->assertFalse(isset($entity->{'$schema'}));
        $this->assertEquals((object)['bar' => 'baz'], $entity->data);
    }

    /**
     * Provide data for testing 'set' method
     *
     * @return array
     */
    public function setProvider()
    {
        return [
            ['data', ['foo' => 'bar']],
            [['data' => ['foo' => 'bar']], null],
        ];
    }

    /**
     * Test 'set' method
     *
     * @dataProvider setProvider
     */
    public function testSet($key, $value)
    {
        $entity = $this->getEntity();

        $result = isset($value) ?
            $entity->set($key, $value) :
            $entity->set($key);

        $this->assertSame($entity, $result);
        $this->assertEquals((object)['foo' => 'bar'], $entity->data);
    }

    /**
     * Test 'fromData' method
     */
    public function testFromData()
    {
        $source = $this->getEntity();
        $class = get_class($source);
        $entity = $class::fromData(['$schema' => 'foo', 'data' => ['bar' => 'baz']]);

        $this->assertInstanceOf($class, $entity);
        $this->assertSame('foo', $entity->schema);
        $this->assertFalse(isset($entity->{'$schema'}));
        $this->assertEquals((object)['bar' => 'baz'], $entity->data);
    }

    /**
     * Test 'jsonSerialize' method
     */
    public function testJsonSerialize()
    {
        $entity = $this->getEntity();
        $entity->schema = 'foo';
        $entity->data = (object)['foo' => 'bar'];

        $result = $entity->jsonSerialize();

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertSame('foo', $result->{'$schema'});
        $this->assertFalse(isset($result->schema));
        $this->assertEquals((object)['foo' => 'bar'], $result->data);
    }

    /**
     * Get test entity
     *
     * @return BasicEntity
     */
    protected function getEntity()
    {
        return new class() extends BasicEntity
        {
            public $schema;

            /**
             * @var object
             **/
            public $data;
        };
    }
}
