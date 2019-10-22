<?php
declare(strict_types=1);

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

    /**
     * Get instruction as string
     *
     * @return string
     */
    public function getInstruction(): string
    {
        $vars = get_object_vars($this);

        foreach ($vars as $key => $value) {
            if (strpos($key, '<') === 0) {
                return $value;
            }
        }

        return '';
    }
}
