<?php

use Jasny\DB\Entity;
use Jasny\ValidationResult;

/**
 * @covers DeepClone
 */
class DeepCloneTest extends \Codeception\Test\Unit
{
    /**
     * Test '__clone' method
     */
    public function testClone()
    {
        $object = $this->getTestObject();
        $result = clone $object;

        $objectBar = $this->getPrivateProperty($object, 'bar');
        $resultBar = $this->getPrivateProperty($result, 'bar');

        $this->assertEquals($object->foo, $result->foo);
        $this->assertEquals($object->foo->bar, $result->foo->bar);
        $this->assertEquals($object->foo->as_array['nested'], $result->foo->as_array['nested']);
        $this->assertEquals($objectBar->items['first'], $resultBar->items['first']);
        $this->assertEquals($objectBar->items['second'], $resultBar->items['second']);

        $this->assertNotSame($object->foo, $result->foo);
        $this->assertNotSame($object->foo->bar, $result->foo->bar);
        $this->assertNotSame($object->foo->as_array['nested'], $result->foo->as_array['nested']);
        $this->assertNotSame($objectBar->items['first'], $resultBar->items['first']);
        $this->assertNotSame($objectBar->items['second'], $resultBar->items['second']);
    }

    /**
     * Get object for testing
     *
     * @return object
     */
    protected function getTestObject()
    {        
        $iterator = $this->getTestTraversable();
        $entity2 = $this->createMock(Entity::class);

        return new class($iterator, $entity2) {
            use DeepClone;

            public $foo;
            protected $bar;

            public function __construct($iterator, $entity2) {
                $this->foo = (object)[
                    'bar' => $entity2,
                    'as_array' => [
                        'nested' => (object)['baz' => 'zoo']
                    ]
                ];

                $this->bar = $iterator;
            }
        };
    }

    /**
     * Get test traversable object
     *
     * @return object
     */
    protected function getTestTraversable()
    {
        $entity1 = $this->createMock(Entity::class);

        return new class($entity1) implements IteratorAggregate, ArrayAccess {
            public $items;

            public function __construct($entity1) 
            {
                $this->items = [
                    'first' => $entity1,
                    'second' => [(object)['zoo' => 'baz']]
                ];
            }

            public function getIterator() 
            {
                foreach ($this->items as $key => $item) {
                    yield $key => $item;
                }
            }

            public function offsetExists($offset) 
            {
                return isset($this->items[$offset]);
            }

            public function offsetGet($offset)
            {
                return $this->offsetExists($offset) ? $this->items[$offset] : null;
            }

            public function offsetSet($offset, $value)
            {
                $this->items[$offset] = $value;
            }

            public function offsetUnset($offset)
            {
                unset($this->items[$offset]);
            }
        };
    }

    /**
     * Get object private property
     *
     * @param object $object
     * @param string $name 
     * @return mixed
     */
    protected function getPrivateProperty($object, $name)
    {
        $refl = new ReflectionObject($object);
        $property = $refl->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
