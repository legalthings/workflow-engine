<?php
declare(strict_types=1);

use Jasny\DB\Dataset\Sorted;
use Jasny\DB\Mongo;
use Jasny\DB\Entity\Enrichable;
use Jasny\DB\Mongo\DB;
use function Jasny\object_get_properties;

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
     * @return array|stdClass
     */
    public static function decodeUnicodeChars($value)
    {
        if (is_array($value) || $value instanceof \stdClass) {
            $out = [];
            
            foreach ($value as $k => $v) {
                // Unescape special characters in keys
                if (is_string($k) && (strpos($k, '\\\\') !== false || strpos($k, '\\u') !== false)) {
                    $key = json_decode('"' . addcslashes($k, '"') . '"');
                } else {
                    $key = $k;
                }
                
                $out[$key] = self::decodeUnicodeChars($v); // Recursion
            }
            
            return is_numeric_array($value) ? $out : (object)$out;
        }
        
        return $value;
    }

    /**
     * Set values
     *
     * @param array|stdClass $values
     * @return $this
     */
    public function setValues($values)
    {
        parent::setValues(array_rename_key((array)$values, '$schema', 'schema'));
        $this->cast();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromData($data)
    {
        $data = self::decodeUnicodeChars($data);

        return parent::fromData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function toData(): array
    {
        $data = parent::toData();

        return array_intersect_key($data, ['_id' => null] + object_get_properties($this, true));
    }

    /**
     * Fetch all documents as data (no ORM).
     *
     * @param array     $filter
     * @param array     $sort
     * @param int|array $limit  Limit or [limit, offset]
     * @param array     $opts
     * @return array
     */
    public static function fetchList(array $filter = [], $sort = [], $limit = null, array $opts = []): array
    {
        $collection = static::getCollection();

        // Sort
        if (is_a(get_called_class(), Sorted::class, true)) {
            $sort = (array)$sort + static::getDefaultSorting();
        }

        $sort = DB::sortToQuery($sort);

        // Limit / skip
        list($limit, $skip) = (array)$limit + [null, null];

        // Find options
        $findOpts = [];
        if ($sort) {
            $findOpts['sort'] = $sort;
        }
        if (isset($limit)) {
            $findOpts['limit'] = $limit;
        }
        if (isset($skip)) {
            $findOpts['skip'] = $skip;
        }
        if (isset($opts['fields'])) {
            $findOpts['projection'] = array_fill_keys($opts['fields'], 1);
        }

        // Query
        $query = static::filterToQuery($filter, $opts);
        $cursor = $collection->find($query, $findOpts);

        return $cursor->toArrayCast();
    }
}
