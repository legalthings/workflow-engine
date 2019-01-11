<?php

use Jasny\DB\Entity;

/**
 * Basic entity the extends stdClass
 */
class BasicEntity extends stdClass implements Entity
{
    use Entity\Implementation;

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
