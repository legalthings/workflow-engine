<?php declare(strict_types=1);

use Jasny\DB\Entity;

/**
 * Meta data of a scenario or process. Meta data is not shared with other nodes and is mutable.
 */
class Meta extends BasicEntity implements Entity\Dynamic
{
    /**
     * Get the data that needs to be stored in the DB.
     *
     * @return stdClass
     */
    public function toData(): stdClass
    {
        return (object)parent::toData();
    }
}
