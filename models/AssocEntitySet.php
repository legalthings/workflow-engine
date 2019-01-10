<?php

use Improved\IteratorPipeline\Pipeline;
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
            ->apply(function($item) {
                if (isset($item->key)) {
                    unset($item->key);
                }
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
     * Clone all entities
     */
    public function __clone()
    {
        foreach ($this->entities as &$entity) {
            $entity = clone $entity;
        }
    }

    /**
     * Get data that needs stored in the DB
     * 
     * @return array
     */
    public function toData()
    {
        $data = [];
        
        foreach ($this->entities as $key => $entity) {
            $data[] = ['key' => $key] + $entity->toData();
        }
        
        return $data;
    }
}
