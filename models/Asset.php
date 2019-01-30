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
    public $schema = 'https://specs.livecontracts.io/v1.0.0/asset/schema.json#';

    /**
     * @var string
     */
    public $key;


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
