<?php

/**
 * Clone all properties recursively.
 */
trait DeepClone
{
    /**
     * Called for cloned entity
     */
    public function __clone()
    {
        $clone = function ($input, $dump = false) use (&$clone) {
            foreach ($input as $key => &$prop) {
                if ($prop instanceof stdClass) {
                    $prop = $clone(clone $prop);
                } elseif ($prop instanceof Traversable) {
                    $prop = clone $prop;
                    foreach ($clone(iterator_to_array($prop)) as $key => $item) {
                        $prop[$key] = $item;
                    }
                } elseif (is_object($prop)) {
                    $prop = clone $prop;
                } elseif (is_array($prop)) {
                    $prop = $clone($prop);
                }
            }

            return $input;
        };
        
        $clone($this);
    }
}