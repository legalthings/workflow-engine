<?php

use Jasny\DB\Mongo;
use Jasny\DB\Entity\Enrichable;
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
                if (strpos($k, '\\\\') !== false || strpos($k, '\\u') !== false) {
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
     * Get type cast object
     *
     * @return Mongo\TypeCast
     */
    protected function typeCast($value)
    {
        $typecast = TypeCast::value($value);

        $typecast->alias('self', get_class($this));
        $typecast->alias('static', get_class($this));

        return $typecast;
    }
}
