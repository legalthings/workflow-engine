<?php

use Improved\IteratorPipeline\Pipeline;
use Jasny\DB\Entity;
use Jasny\DB\EntitySet;

/**
 * EntitySet with associative keys.
 */
class AssocEntitySet extends EntitySet
{
    /** Automatically create entities */
    const AUTOCREATE = 0x100;

    /**
     * Default flags
     * @var type 
     */
    protected $flags = EntitySet::PRESERVE_KEYS | EntitySet::ALLOW_DUPLICATES | self::AUTOCREATE;

    /**
     * Class constructor
     *
     * @param Entities[]|\Traversable $entities  Array of entities
     * @param int|\Closure            $total     Total number of entities (if set is limited)
     * @param int                     $flags     Control the behaviour of the entity set
     */
    public function __construct($entities = [], $total = null, $flags = 0)
    {
        parent::__construct($this->convertArrayToAssoc($entities), $total, $flags);
    }

    /**
     * Convert an ordered array with object with a key into an associated array
     * 
     * @param array $input
     * @return array
     */
    protected function convertArrayToAssoc($input)
    {
        return Pipeline::with($input)
            ->map(function($item) {
                return (object)$item;
            })
            ->mapKeys(function($item, $key) {
                return $item->key ?? $key;
            })
            ->apply(function($item, $key) {
                $item->key = $key;
            });
    }
    
    /**
     * Set the entities
     * 
     * @param array|\Traversable $input
     */
    protected function setEntities($input)
    {
        $assoc = $this->convertArrayToAssoc($input);
        $entities = $this->castEntities($assoc);
        
        if (~$this->flags & self::ALLOW_DUPLICATES) {
            $entities = $this->uniqueEntities($entities);
        }

        if (~$this->flags & self::PRESERVE_KEYS) {
            $entities = array_values($entities);
        }

        $this->entities = $entities;
    }

    /**
     * Return all keys from entities
     * 
     * @return array
     */
    public function getKeys() 
    {
        return array_keys($this->entities);
    }

    /**
     * Replace the entity of a specific index
     *
     * @param string       $index
     * @param Entity|array $entity  Entity or data representation of entity
     */
    public function offsetSet($index, $entity)
    {
        $this->assertEntity($entity);

        $index = $index ?? $entity->key;
        $entity->key = $index;

        parent::offsetSet($index, $entity);
    }

    /**
     * Clone all entities
     */
    public function __clone()
    {
        foreach ($this->entities as &$entity) {
            $entity = clone $entity;
        }
    }

    /**
     * Get data representation for saving to DB.
     *
     * @return array
     */
    public function toData(): array
    {
        return Pipeline::with($this->entities)
            ->map(function(Entity $entity, string $key) {
                return ['key' => $key] + $entity->toData();
            })
            ->values()
            ->toArray();
    }
}
