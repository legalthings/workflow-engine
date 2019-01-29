<?php

use Jasny\DotKey;
use Jasny\DB\Entity\Dynamic;
use function JmesPath\search as jmespath_search;
use function Jasny\object_set_properties;

/**
 * Patch data.
 */
class DataPatcher
{
    /**
     * Set value(s) by selector.
     *
     * @param array|object $input
     * @param string       $selector
     * @param mixed        $value
     * @param bool         $patch
     */
    public function set(&$input, string $selector, $value, bool $patch = true): void
    {
        $dotkey = DotKey::on($input);

        if ($patch && (is_array($value) || is_object($value))) {
            $target = $dotkey->get($selector);

            if (is_object($target) && !$target instanceof ArrayAccess) {
                $dynamic = $target instanceof stdClass || $target instanceof Entity\Dynamic;
                object_set_properties($target, $value, $dynamic);
                return;
            }

            if (is_array($target) || $target instanceof ArrayAccess) {
                $value = $this->patchArray($target, $value);
            }
        }

        $input = $dotkey->put($selector, $value);
    }
    
    /**
     * Set value by selector using patch.
     *
     * @param array|ArrayAccess $target
     * @param array|object      $value
     * @return array|ArrayAccess
     */
    protected function patchArray($array, $value)
    {
        foreach ($value as $key => $item) {
            $array[$key] = $item;
        }

        return $array;
    }

    /**
     * Project the input based on jmespath
     * 
     * @param object|array $input
     * @param string       $jmespath
     * @return mixed
     */
    public function project($input, string $jmespath)
    {
        try {
            return jmespath_search($jmespath, (array)$input);
        } catch (JmesPath\SyntaxErrorException $e) {
            throw new RuntimeException("JMESPath projection failed: " . $e->getMessage(), 0, $e);
        }
    }
}
