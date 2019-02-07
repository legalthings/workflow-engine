<?php declare(strict_types=1);

use Improved as i;
use Jasny\DotKey;

/**
 * Patch an object, by setting the selected value.
 * Optionally the selected value can be merged following RFC-7396.
 */
class DataPatcher
{
    /**
     * @var callable
     */
    protected $jmespath;


    /**
     * DataPatcher constructor.
     *
     * @param callable $jmespath  "jmespath"
     */
    public function __construct(callable $jmespath)
    {
        $this->jmespath = $jmespath;
    }

    /**
     * Set value(s) by selector.
     *
     * @param array|object $subject
     * @param string       $selector  Path in JavaScript dot notation.
     * @param mixed        $value
     * @param bool         $merge     Merge objects as RFC-7396 or add an item to an array.
     */
    public function set(&$subject, string $selector, $value, bool $merge = false): void
    {
        $dotkey = DotKey::on($subject);
        $target = $dotkey->get($selector);

        if ($merge) {
            $value = $this->merge($target, $value);
        }

        if ($value === null) {
            $dotkey->remove($selector);
        } else {
            $subject = $dotkey->put($selector, $value);
        }
    }

    /**
     * Merge the target with the given values.
     *
     * @param mixed $target
     * @param mixed $value
     * @return mixed
     */
    public function merge($target, $value)
    {
        if ($this->isArrayish($target)) {
            $target[] = $value;
            return $target;
        }

        if (!is_associative_array($value) && !is_object($value)) {
            return $value;
        }

        if (is_associative_array($target) || $target instanceof ArrayAccess) {
            return $this->mergeAssoc($target, $value);
        }

        if (is_object($target)) {
            return $this->mergeObject($target, $value);
        }

        return $value;
    }

    /**
     * Check if the target is a numeric array or (numeric array object).
     * Empty arrays are undefined, but also seen as numeric for this case.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isArrayish($value): bool
    {
        if ($value instanceof ArrayAccess && is_iterable($value) && !$value instanceof AssocEntitySet) {
            $value = i\iterable_to_array($value);
        }

        return $value === [] || is_numeric_array($value);
    }

    /**
     * Merge object with the given values.
     *
     * @param object        $target
     * @param array|object  $value
     * @return array|ArrayAccess
     */
    protected function mergeObject($target, $value)
    {
        foreach ($value as $key => $item) {
            if ($item === null && !property_exists(get_class($target), $key)) {
                unset($target->$key);
                continue;
            }

            $target->$key = isset($target->$key) && (is_associative_array($item) || is_object($item))
                ? $this->merge($target->$key, $item)
                : $item;
        }

        return $target;
    }

    /**
     * Merge associated array with the given values.
     *
     * @param array|ArrayAccess $target
     * @param array|object      $value
     * @return array|ArrayAccess
     */
    protected function mergeAssoc($target, $value)
    {
        foreach ($value as $key => $item) {
            if ($item === null) {
                unset($target[$key]);
                continue;
            }

            $target[$key] = isset($target[$key]) && (is_associative_array($item) || is_object($item))
                ? $this->merge($target[$key], $item)
                : $item;
        }

        return $target;
    }


    /**
     * Project the input based on jmespath.
     * 
     * @param object|array $input
     * @param string       $projection
     * @return mixed
     */
    public function project($input, string $projection)
    {
        try {
            return i\function_call($this->jmespath, $projection, $input);
        } catch (JmesPath\SyntaxErrorException $e) {
            throw new RuntimeException("JMESPath projection failed: " . $e->getMessage(), 0, $e);
        }
    }
}
