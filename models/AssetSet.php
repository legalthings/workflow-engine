<?php

use Jasny\DB\EntitySet;
use Jasny\DB\Entity;

/**
 * EntitySet for assets.
 * This set is recursive; AssetSet may contain Asset and/or AssetSet objects.
 */
class AssetSet extends AssocEntitySet
{
    /**
     * @var int 
     */
    protected $flags = EntitySet::ALLOW_DUPLICATES | EntitySet::PRESERVE_KEYS | AssocEntitySet::AUTOCREATE;
    
    /**
     * The class name of the entities in this set
     * @var string
     */
    protected $entityClass = Asset::class;


    /**
     * Turn input into array of entities
     * 
     * @param Entity|array|mixed $entity
     */
    protected function assertEntity($entity)
    {
        if (is_numeric_array($entity) || $entity instanceof Traversable) {
            foreach ($entity as $subEntity) {
                $this->assertEntity($subEntity);
            }
            return;
        }
        
        if (!$entity instanceof Entity) {
            $type = (is_object($entity) ? get_class($entity) . ' ' : '') . gettype($entity);
            throw new \InvalidArgumentException("A $type is not an Entity");
        }
        
        if (!isset($this->entityClass)) {
            $this->setEntityClass(get_class($entity));
        }
        
        if (!is_a($entity, $this->entityClass)) {
            throw new \InvalidArgumentException(get_class($entity) . " is not a {$this->entityClass} entity");
        }
    }
    
    /**
     * Turn item into entity or entity set
     * 
     * @param mixed $item
     * @return Asset|AssetSet
     */
    protected function castEntity($item)
    {
        if ($item instanceof self || $item instanceof Entity) {
            return $item;
        }

        if (is_numeric_array($item) || $item instanceof Traversable) {
            $subset = static::forClass($this->getEntityClass(), [], null, $this->getFlags());
            
            foreach ($item as $subEntity) {
                $subset[] = $this->castEntity($subEntity);
            }
            return $subset;
        }
        
        return parent::castEntity($item);
    }
    
    /**
     * Replace the entity of a specific index
     * 
     * @param int          $index
     * @param Entity|array $entity  Entity or data representation of entity
     */
    public function offsetSet($index, $entity)
    {
        if (!$entity instanceof Entity) {
            $entity = $this->castEntity($entity);
        }
        
        return parent::offsetSet($index, $entity);
    }
    
    /**
     * Expand all entities and remove permanent ghosts
     * 
     * @param array $opts
     * @return $this
     */
    public function expand(array $opts = [])
    {
        foreach ($this->entities as $i => $entity) {
            if ($entity instanceof Entity\LazyLoading) {
                $entity->expand($opts);
                if ($entity->isGhost()) {
                    unset($this->entities[$i]);
                }
            } elseif ($entity instanceof self) {
                $entity->expand($opts);
            }
        }
        
        if (~$this->flags & self::PRESERVE_KEYS) {
            $this->entities = array_values($this->entities);
        }
        
        return $this;
    }
    
    /**
     * Set the values of all entities
     * 
     * @param array $allValues
     * @return $this
     */
    public function patch(array $allValues)
    {
        foreach ($allValues as $key => $values) {
            if (!isset($this->entities[$key])) {
                $this[$key] = $values;
            } elseif ($this->entities[$key] instanceof AssetSet) {
                $this->entities[$key]->patch($values);
            } else {
                $this->entities[$key]->setValues($values);
            }
        }
        
        return $this;
    }

    /**
     * Prepare for JSON serialization
     *
     * @return \stdClass
     */
    public function jsonSerialize()
    {
        return (object)parent::jsonSerialize();
    }
}
