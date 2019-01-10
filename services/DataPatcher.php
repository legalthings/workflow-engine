<?php

use Jasny\DotKey;
use Jasny\DB\Entity;
use Jasny\DB\EntitySet;

/**
 * Patch data
 */
class DataPatcher
{
    /**
     * Set value(s) by selector
     *
     * @param mixed   $input
     * @param string  $selector
     * @param mixed   $value
     * @param boolean $patch
     */
    public function set(&$input, $selector, $value, $patch = true)
    {
        $dotkey = DotKey::on($input);

        if (substr($selector, 0, 2) === '$.') {
            $selector = substr($selector, 2);
        }

        if ($patch) {
            self::patch($input, $dotkey, $selector, $value);
        } elseif ($selector === '$') {
            throw new RuntimeException("Unable to update object with '$' selector without patch option");
        } else {
            $dotkey->put($selector, $value);
        }
    }
    
    /**
     * Set value by selector using patch
     * 
     * @param DotKey $dotkey
     * @param string $selector
     * @param mixed  $value
     */
    public function patch(&$input, DotKey $dotkey, $selector, $value)
    {
        $target = $selector === '$' ? $input : $dotkey->get($selector);

        if ($target instanceof Entity) {
            $target->setValues($value);
            return;
        }

        if ($target instanceof EntitySet && (is_array($value) || $value instanceof stdClass)) {
            self::patchEntitySet($target, $selector, $value);
            return;
        }

        if (is_array($value) || $value instanceof stdClass) {
            if (is_array($target)) {
                $value = array_merge($target, $value);
            } elseif ($target instanceof stdClass) {
                $value = (object)array_merge((array)$target, (array)$value);
            }
        }
        
        $dotkey->put($selector, $value);
    }

    /**
     * Patch entity set by selector
     * 
     * @param EntitySet      $input
     * @param string         $selector
     * @param array|stdClass $value
     */
    public function patchEntitySet(EntitySet &$target, $selector, $value)
    {
        $autoCreate = ($target instanceof AssocEntitySet) && ($target->getFlags() & AssocEntitySet::AUTOCREATE) !== 0;

        foreach ($value as $key => $input) {
            if (isset($target[$key])) {
                $target[$key]->setValues($input);
            } elseif ($autoCreate) {
                $target[$key] = $input;
            } else {
                trigger_error("$selector.$key doesn't exist", E_USER_WARNING);
                continue;
            }
        }
    }
    
    /**
     * Project the input based on jmespath
     * 
     * @param object|array $input
     * @param string|null  $jmespath
     * 
     * @return mixed
     */
    public function project($input, $jmespath = null)
    {
        if (!isset($jmespath)) {
            return $input;
        }
        
        try {
            return JmesPath\search($jmespath, (array)$input);
        } catch (JmesPath\SyntaxErrorException $e) {
            throw new RuntimeException("JMESPath projection failed: " . $e->getMessage(), 0, $e);
        }
    }
}
