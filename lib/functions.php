<?php

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
