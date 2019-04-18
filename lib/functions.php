<?php

use Improved as i;
use function Jasny\object_get_properties;

function get_dynamic_propeties($object)
{
    $allProps = get_object_vars($object);
    $classProps = get_object_vars(get_class($object));

    return array_diff_key($allProps, $classProps);
}

/**
 * Flatten an array, concatenating the keys
 * 
 * @param string $glue
 * @param array  $array
 */
function flatten($array)
{
    foreach ($array as $key => &$value) {
        if (!is_associative_array($value)) {
            continue;
        }

        unset($array[$key]);
        $value = flatten($value);

        foreach ($value as $subkey => $subvalue) {
            $array["$key.$subkey"] = $subvalue;
        }
    }
    
    return $array;
}

/**
 * Check if values from the first array/object are matches in the seccond
 * 
 * @param mixed $first
 * @param mixed $second
 * @return boolean
 */
function compare_assoc($first, $second)
{
    if ((!is_array($first) && !is_object($first)) || (!is_array($second) && !is_object($second))) {
        return $first == $second;
    }
    
    foreach ($first as $key => $value) {
        if (!isset($value)) {
            continue;
        }
        
        if (
            !array_key_exists($key, $second) ||
            !compare_assoc($value, is_object($second) ? $second->$key : $second[$key])
        ) {
            return false;
        }
    }
    
    return true;
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
 * Get parameters names of given method
 * @param  string $class
 * @param  string $method
 * @return array
 */
function get_method_args_names(string $class, string $method)
{
    $reflection = new ReflectionMethod($class, $method);
    $params = $reflection->getParameters();

    return array_map(function($item) {
        return $item->getName();
    }, $params);
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

/**
 * Assert that value is iterable
 * @param  iterable $iterable
 * @param  array $types
 * @throws UnexpectedValueException
 */
function type_check_iterable(iterable $iterable, array $types)
{
    foreach ($iterable as $item) {
        i\type_check($item, $types);
    }
}
