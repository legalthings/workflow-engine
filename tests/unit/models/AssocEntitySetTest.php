<?php

use Jasny\DB\Entity;
use Jasny\DB\Entity\Identifiable;
use Jasny\DB\EntitySet;

/**
 * @covers AssocEntitySet
 */
class AssocEntitySetTest extends \Codeception\Test\Unit
{
    use Jasny\TestHelper;

    /**
     * Test '__construct' method
     */
    public function testConstruct()
    {
        $entities = $this->getEntities();        
        $clones = $this->getExpectedEntities($entities);

        $result = new AssocEntitySet($entities);
        $array = $result->getArrayCopy();

        $expected = [
            'foo' => $clones[0],
            1 => $clones[1],
            'bar' => $clones[2],
            3 => $clones[3],
            'baz' => $clones[4]
        ];

        $this->assertEquals($expected, $array);
    }

    /**
     * Test '__construct' method, if no duplicates are allowed
     */
    public function testConstructNoDuplicates()
    {
        $refl = new \ReflectionClass(AssocEntitySet::class);
        
        $entities = $this->getEntities(Identifiable::class);        
        $clones = $this->getExpectedEntities($entities);
        $this->mockDuplicates($entities);
        
        $set = $refl->newInstanceWithoutConstructor();
        $this->setPrivateProperty($set, 'flags', ~EntitySet::ALLOW_DUPLICATES);
        $set->__construct($entities);

        $result = $set->getArrayCopy();

        $expected = [
            'foo' => $clones[0],
            1 => $clones[1],
            'bar' => $clones[2]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test '__construct' method, if keys should not be preserved
     */
    public function testConstructNoPreserveKeys()
    {
        $refl = new \ReflectionClass(AssocEntitySet::class);
        
        $entities = $this->getEntities(Identifiable::class);        
        $clones = $this->getExpectedEntities($entities);
        
        $set = $refl->newInstanceWithoutConstructor();
        $this->setPrivateProperty($set, 'flags', ~EntitySet::PRESERVE_KEYS);
        $set->__construct($entities);

        $result = $set->getArrayCopy();

        $expected = [
            $clones[0],
            $clones[1],
            $clones[2],
            $clones[3],
            $clones[4]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test 'getKeys' method
     */
    public function testGetKeys()
    {
        $entities = $this->getEntities();        
        $set = new AssocEntitySet($entities);

        $result = $set->getKeys();

        $expected = ['foo', 1, 'bar', 3, 'baz'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test '__clone' method
     */
    public function testClone()
    {
        $entities = $this->getEntities();        
        $set = new AssocEntitySet($entities);

        $clone = clone $set;        
        $cloned = $clone->getArrayCopy();
        $original = $set->getArrayCopy();

        $this->assertSame(count($original), count($entities));
        $this->assertSame(count($original), count($cloned));

        foreach ($original as $key => $originalEntity) {
            $this->assertEquals($originalEntity, $cloned[$key]);
            $this->assertNotSame($originalEntity, $cloned[$key]);
        }
    }

    /**
     * Test 'offsetSet' method, when setting assoc key explicitly for array
     */
    public function testOffsetSetArray()
    {
        $set = new AssocEntitySet();

        $class = $this->getEntityClass();
        $this->setPrivateProperty($set, 'entityClass', $class);

        $set->offsetSet('foo', ['bar' => 'bar_value']);

        $result = $set->getArrayCopy();

        $this->assertInstanceOf($class, $result['foo']);
        $this->assertSame($result['foo']->bar, 'bar_value');
    }

    /**
     * Test 'offsetSet' method, when setting assoc key explicitly for entity
     */
    public function testOffsetSetEntity()
    {
        $entity = $this->createMock(Entity::class);

        $set = new AssocEntitySet();
        $set->offsetSet('foo', $entity);

        $result = $set->getArrayCopy();

        $this->assertSame($result['foo'], $entity);
    }

    /**
     * Test 'offsetSet' method, when setting assoc key implicitly
     */
    public function testOffsetSetNull()
    {
        $entity = $this->createMock(Entity::class);
        $entity->key = 'foo';

        $set = new AssocEntitySet();
        $set->offsetSet(null, $entity);

        $result = $set->getArrayCopy();

        $this->assertSame($result['foo'], $entity);
    }

    /**
     * Test 'toData' method
     */
    public function testToData()
    {
        $entities = $this->getEntities();        
        $this->mockToData($entities);

        $set = new AssocEntitySet($entities);
        $result = $set->toData();

        $expected = [
            ['idx' => 0, 'key' => 'foo'],
            ['idx' => 1, 'key' => 1],
            ['idx' => 2, 'key' => 'bar'],
            ['idx' => 3, 'key' => 3],
            ['idx' => 4, 'key' => 'baz']
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Get class for custom entity
     *
     * @return string
     */
    protected function getEntityClass()
    {
        $object = new class() extends BasicEntity {
            public $bar;
        };

        return get_class($object);
    }

    /**
     * Mock duplicated entities
     *
     * @param array $entities
     */
    protected function mockDuplicates($entities)
    {
        $entities[0]->expects($this->any())->method('getId')->willReturn('a');
        $entities[1]->expects($this->any())->method('getId')->willReturn('b');
        $entities[3]->expects($this->any())->method('getId')->willReturn('b');
        $entities[2]->expects($this->any())->method('getId')->willReturn('c');
        $entities[4]->expects($this->any())->method('getId')->willReturn('c');
    }

    /**
     * Get entities
     *
     * @return array
     */
    protected function getEntities($class = Entity::class)
    {
        $entities = [
            $this->createMock($class),
            $this->createMock($class),
            $this->createMock($class),
            $this->createMock($class),
            $this->createMock($class)
        ];   

        $entities[0]->key = 'foo';
        $entities[2]->key = 'bar';
        $entities[4]->key = 'baz';

        return $entities;
    }

    /**
     * Mock toData method call
     *
     * @param array $entities
     * @return array
     */
    protected function mockToData($entities)
    {
        foreach ($entities as $key => $entity) {
            $entity->expects($this->once())->method('toData')->willReturn(['idx' => $key]);
        }
    }

    /**
     * Get entities clones
     *
     * @return array
     */
    protected function getExpectedEntities($entities)
    {
        $expected = [];

        foreach ($entities as $key => $entity) {
            $entity = clone $entity;

            if (!isset($entity->key)) {
                $entity->key = $key;
            }

            $expected[] = $entity;
        }

        return $expected;
    }
}
