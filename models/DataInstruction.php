<?php

use Jasny\DB\Entity\Dynamic;

/**
 * Data instruction
 */
class DataInstruction extends BasicEntity implements Dynamic
{
    /**
     * Convert loaded values to an entity
     *
     * @param array|stdClass|string $data
     * @return static
     */
    public static function fromData($data): DataInstruction
    {
        return parent::fromData(objectify($data));
    }
}
