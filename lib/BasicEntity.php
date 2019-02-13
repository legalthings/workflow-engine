<?php

use Jasny\DB\Entity;
use Jasny\DB\Entity\Meta;

/**
 * Basic entity the extends stdClass
 */
class BasicEntity extends stdClass implements Entity, Meta
{
    use Entity\Implementation,
        Meta\Implementation
    {
        Meta\Implementation::jsonSerializeFilter insteadof Entity\Implementation;
        setValues as private _setValues;
        fromData as private _fromData;
        jsonSerialize as private _jsonSerialize;
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
    public function setValues($values)
    {
        $this->_setValues(array_rename_key($values, '$schema', 'schema'));
        $this->cast();

        return $this;
    }

    /**
     * Set one or more values.
     *
     * @param string|array $key
     * @param mixed        $value
     * @return $this
     */
    public function set($key, $value = null)
    {
        $values = func_get_args() === 1 ? $key : [$key => $value];

        return $this->setValues($values);
    }

    /**
     * Convert loaded values to an entity.
     * Calls the construtor *after* setting the properties.
     *
     * @param array|stdClass $values
     * @return static
     */
    public static function fromData($values)
    {
        return self::_fromData(array_rename_key((array)$values, '$schema', 'schema'));
    }

    /**
     * Prepare json serialization
     *
     * @return object
     */
    public function jsonSerialize()
    {
        $object = $this->_jsonSerialize();
        $object = object_rename_key($object, 'schema', '$schema');

        return $object;
    }
}
