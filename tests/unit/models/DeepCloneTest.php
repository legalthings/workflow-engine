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

        $resultBar = $this->getPrivateProperty($result, 'bar');

        $this->assertEquals($object->foo, $result->foo);
        $this->assertEquals($object->foo->bar, $result->foo->bar);
        $this->assertEquals($object->foo->as_array['nested'], $result->foo->as_array['nested']);
        $this->assertInstanceOf(Entity::class, $resultBar->first);
        $this->assertTrue(is_array($resultBar->second));
        $this->assertSame('baz', $resultBar->second[0]->zoo);

        $this->assertNotSame($object->foo, $result->foo);
        $this->assertNotSame($object->foo->bar, $result->foo->bar);
        $this->assertNotSame($object->foo->as_array['nested'], $result->foo->as_array['nested']);
    }

    /**
     * Get object for testing
     *
     * @return object
     */
    protected function getTestObject()
    {
        $entity1 = $this->createMock(Entity::class);
        $entity2 = $this->createMock(Entity::class);

        $iterator = new class($entity1) implements IteratorAggregate {
            public function __construct($entity1) {
                $this->entity1 = $entity1;
            }

            public function getIterator() {
                $items = [
                    'first' => $this->entity1,
                    'second' => [(object)['zoo' => 'baz']]
                ];

                foreach ($items as $key => $item) {
                    yield $key => $item;
                }
            }
        };

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
