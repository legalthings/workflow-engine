<?php

namespace Helper;

use PHPUnit\Framework\Assert;

/**
 * Adds functionality to $I
 */
class General extends \Codeception\Module
{
    /**
     * Check if list is ordered
     * 
     * @param array  $list
     * @param string $property    Sorting property for a list of objects or arrays
     * @param int    $sort_flags
     * @return boolean
     */
    public function seeListIsOrdered($list, $sort_flags = SORT_REGULAR)
    {
        $original = $list;
        sort($list, $sort_flags);
        
        return $list === $original;
    }
    
    /**
     * Check if list is ordered
     * 
     * @param string $property
     * @param array  $list
     * @param int    $sort_flags
     * @return boolean
     */
    public function seeListIsOrderedBy($property, $list, $sort_flags = SORT_REGULAR)
    {
        $propList = array_map(function($item) use ($property) {
            if (is_array($item)) $item = (object)$item;
            return isset($item->$property) ? $item->$property : null;
        }, $list);
        
        return $this->seeListIsOrdered($propList, $sort_flags);
    }
    
    /**
     * Generate a number of random characters
     * 
     * @param int $length
     * @return string
     */
    public function generateRandomCharacters($length)
    {
        return $s = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", $length)), 0, $length);
    }

    /**
     * Assert array contains an element or string contains a substring
     * 
     * @param string|mixed $needle
     * @param string|array $haystack
     * @param string       $message
     * @throws \PHPUnit_Framework_ExpectationFailedException
     */
    public function assertContains($needle, $haystack, $message = [])
    {
        Assert::assertContains($needle, $haystack, $message);
    }
    
    /**
     * Assert array contains an element or string contains a substring
     * 
     * @param int          $expectedCount
     * @param string|array $haystack
     * @param string       $message
     * @throws \PHPUnit_Framework_ExpectationFailedException
     */
    public function assertCount($expectedCount, $haystack, $message = [])
    {
        Assert::assertCount($expectedCount, $haystack, $message);
    }
    
    /**
     * Asserts that an array has a specified key.
     *
     * @param mixed             $key
     * @param array|ArrayAccess $array
     * @param string            $message
     */
    public function assertArrayHasKey($key, $array, $message = null)
    {
        Assert::assertArrayHasKey($key, $array, $message);
    }
    
    /**
     * Asserts that an array doesn't have a specified key.
     *
     * @param mixed             $key
     * @param array|ArrayAccess $array
     * @param string            $message
     */
    public function assertArrayNotHasKey($key, $array, $message = null)
    {
        Assert::assertArrayNotHasKey($key, $array, $message);
    }
    
    /**
     * Asserts that an array has a specified subset.
     *
     * @param array|ArrayAccess $subset
     * @param array|ArrayAccess $array
     * @param bool              $strict  Check for object identity
     * @param string            $message
     */
    public function assertArraySubset($subset, $array, $strict = false, $message = null)
    {
        Assert::assertArraySubset($subset, $array, $strict, $message);
    }
    
    /**
     * Asserts that an array item has a specified value.
     *
     * @param mixed             $expected
     * @param mixed             $key
     * @param array|ArrayAccess $array
     * @param string            $message
     */
    public function assertArrayItemEquals($expected, $key, $array, $message = null)
    {
        $this->assertArrayHasKey($key, $array, $message);
        $this->assertEquals($expected, $array[$key], $message);
    }
}
