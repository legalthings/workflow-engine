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
        if (is_string($data)) {
            if (!preg_match('|^\![a-z]+\s\S+|', $data)) {
                throw new InvalidArgumentException("Invalid format for data instruction");
            }

            list($type, $expression) = explode(' ', $data, 2);
            $type = trim($type, '!');

            $data = ["<$type>" => $expression];
        }

        return parent::fromData($data);
    }

    /**
     * Cast to string
     *
     * @return string
     */
    public function __toString(): string
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
