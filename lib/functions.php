<?php

use Improved as i;
use function Jasny\object_get_properties;

/**
 * Get name of properties that an object has, but aren't defined by the class.
 *
 * @param object $object
 * @return array
 */
function get_dynamic_properties($object)
{
    $allProps = get_object_vars($object);
    $classProps = get_class_vars(get_class($object));

    return array_diff(array_keys($allProps), array_keys($classProps));
}

/**
 * Rename the key of an associative array.
 *
 * @param array  $array
 * @param string $from
 * @param string $to
 * @return array
 */
function array_rename_key(array $array, string $from, string $to)
{
    if (array_key_exists($from, $array)) {
        $array[$to] = $array[$from];
        unset($array[$from]);
    }

    return $array;
}

/**
 * Rename the property of an object.
 *
 * @param object $object
 * @param string $from
 * @param string $to
 * @return array
 */
function object_rename_key($object, string $from, string $to)
{
    if (property_exists($object, $from)) {
        $object->$to = $object->$from;
        unset($object->$from);
    }

    return $object;
}

/**
 * Copy all properties from one object to another.
 * Clone property if it's an object.
 *
 * @param object $from
 * @param object $to
 * @return object $to
 */
function object_copy_properties($from, $to)
{
    $properties = array_keys(object_get_properties($to));

    foreach ($properties as $property) {
        $value = $from->$property ?? null;

        if (is_object($value)) {
            $value = clone $value;
        }

        $to->$property = $value;
    }

    return $to;
}

/**
 * Keep only specified properties in stdClass object
 * @param  stdClass $object
 * @param  array    $with
 * @return stdClass
 */
function std_object_only_with(stdClass $object, array $with)
{
    $object = array_only((array)$object, $with);

    return (object)$object;
}

/**
 * Check if link to schema specification is valid
 * @param  string  $link
 * @param  string  $type
 * @return boolean
 */
function is_schema_link_valid(string $link, string $type)
{
    $pattern = '|https://specs\.livecontracts\.io/v\d+\.\d+\.\d+/' . preg_quote($type) . '/schema\.json#|';

    return (bool)preg_match($pattern, $link);
}
