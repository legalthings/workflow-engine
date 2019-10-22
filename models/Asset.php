<?php
declare(strict_types=1);

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
     * @var string
     */
    public $key;
}
