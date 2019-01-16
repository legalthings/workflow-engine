<?php

use Jasny\DB\Entity\Dynamic;

/**
 * Storable data
 */
class Asset extends BasicEntity implements Dynamic
{
    use DeepClone;

    /**
     * @var string
     */
    public $schema;


    /**
     * Prepare json serialization
     *
     * @return object
     */
    public function jsonSerialize()
    {
        $object = parent::jsonSerialize();

        foreach ($object as $key => $value) {
            if ($value === null) {
                unset($object->$key);
            }
        }

        return $object;
    }
}
