<?php

use Jasny\DB\Entity;

/**
 * Basic entity the extends stdClass
 */
class BasicEntity extends stdClass implements Entity
{
    use Entity\Implementation {
        setValues as private _setValues;
    }

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->cast();
    }

    /**
     * Set values
     *
     * @param array $values
     * @return $this
     */
    public function setValues($values): self
    {
        $values = array_rename_key($values, '$schema', 'schema');

        return $this->_setValues($values);
    }

    /**
     * Prepare json serialization
     *
     * @return object
     */
    public function jsonSerialize()
    {
        $object = parent::jsonSerialize();
        $object = object_rename_key($object, 'schema', '$schema');

        foreach ($object as $key => $value) {
            if (!isset($value)) {
                unset($object->$key);
            }
        }

        return $object;
    }
}
