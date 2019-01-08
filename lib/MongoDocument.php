<?php

use Jasny\DB\Mongo;
use Jasny\DB\Entity\Enrichable;
use Jasny\DB\Entity;
use Jasny\DB\EntitySet;

/**
 * Base class for Mongo Documents
 */
abstract class MongoDocument extends Mongo\Document implements Enrichable
{
    use Enrichable\Implementation;
    
    /**
     * Decode unicode chars recursively
     * Crutch because jasny/db-mongo doesn't seem to decode the data in fromData
     * 
     * @param array|stdClass $value
     * 
     * @return array|stdClass
     */
    public static function decodeUnicodeChars($value)
    {
        if (is_array($value) || $value instanceof \stdClass) {
            $out = [];
            
            foreach ($value as $k => $v) {
                // Unescape special characters in keys
                if (strpos($k, '\\\\') !== false || strpos($k, '\\u') !== false) {
                    $key = json_decode('"' . addcslashes($k, '"') . '"');
                } else {
                    $key = $k;
                }
                
                $out[$key] = self::decodeUnicodeChars($v); // Recursion
            }
            
            $isNumeric = is_array($value) && (key($value) === 0 &&
                array_keys($value) === array_keys(array_fill(0, count($value), null))) || !count($value);
            
            return !$isNumeric ? (object)$out : $out;
        }
        
        return $value;
    }
    
    /**
     * Index the given sort fields
     * These must be Jasny styled sort fields, example: [^name, date]
     * 
     * @param array $sort
     */
    public static function indexSortFields($sort)
    {
        if (empty($sort) || !isset($sort) || !is_array($sort)) {
            // do not index corrupted sort fields
            return;
        }
        
        $db = static::getDB();
        $querySort = $db::sortToQuery($sort);
        
        if (empty($querySort)) {
            return;
        }
        
        $collection = static::getCollection();
        $collection->createIndex($querySort);
    }
    
    /**
     * Get entity as list item
     *
     * @return object
     */
    public function asListItem()
    {
        return [
            'id' => $this->getId(),
            'name' => (string)$this
        ];
    }

    /**
     * Create a reference from a string
     *
     * @param string $string
     * @return string
     */
    public static function createReference($string)
    {
        if (empty($string)) {
            throw new RuntimeException("Unable to create a reference from an empty string");
        }
        
        $normal = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $string));
        $ref = trim(preg_replace(['/[\-_\s]+/', '/[^\w\-]/'], ['-', ''], $normal));
        
        if (empty($ref)) {
            throw new RuntimeException("'$string' results in an empty reference");
        }
        
        return $ref;
    }

    /**
     * Create a random string
     *
     * @param int $length (optional)
     * @return string
     */
    public static function randomString($length = null)
    {
        $length = isset($length) ? $length : 10;
        $randomString = substr(str_shuffle(md5(time())), 0, $length);
        return $randomString;
    }

    /**
     * Prepare for JSON serialization
     *
     * @return object
     */
    public function jsonSerialize()
    {
        $object = parent::jsonSerialize();
        //static::jsonSerializeDateConvert($object);

        return $object;
    }

    /**
     * Convert DateTime to date string recursively
     *
     * @param mixed $prop
     */
    protected static function jsonSerializeDateConvert(&$prop)
    {
        if ($prop instanceof \DateTime) {
            $prop = $prop->format('c');
        } elseif (is_array($prop) || is_object($prop)) {
            foreach ($prop as &$value) {
                self::jsonSerializeDateConvert($value);
            }
        }
    }
    
    /**
     * Get the last modified date of the resource based on the given id
     * 
     * @param mixed $id
     * 
     * @return int|null  unix timestamp of the date or null if it doesn't exist
     */
    public static function getModifiedDate($id)
    {
        try {
            if (!static::exists($id)) {
                // resource may have been deleted and should not be cached
                return null;
            }
        } catch (Exception $e) {
            // resource id is most likely an invalid mongo id
            return null;
        }

        $collection = static::getCollection();
        
        if (method_exists($collection, 'withoutCasting')) {
            $collection = $collection->withoutCasting();
        }
        
        $filter = static::idToFilter($id);
        $query = static::filterToQuery($filter);
        $values = $collection->findOne($query, ['last_modified' => 1, 'last_updated' => 1]);
        
        if (isset($values['last_modified'])) {
            $date = $values['last_modified'];
        }
        
        if (isset($values['last_updated'])) {
            $date = $values['last_updated'];
        }
        
        if (!isset($date)) {
            return 0; // oldest timestamp
        }
        
        $date = isset($date->date) ? $date->date : $date; // bc
        
        if ($date instanceof MongoDate) {
            $date = $date->sec;
        }
        
        return $date;
    }
}
