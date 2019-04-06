<?php declare(strict_types=1);

/**
 * Class for type casting
 */
class TypeCast extends Jasny\DB\Mongo\TypeCast
{    
    /**
     * Throw an exception if value can not be cast
     * 
     * @param string $type
     * @param string $explain  Additional message
     * @return mixed
     */
    public function dontCastTo($type, $explain = null)
    {
        $valueType = $this->getValueTypeDescription();
        
        if (!strstr($type, '|')) {
            $type = (in_array($type, ['array', 'object']) ? 'an ' : 'a ') . $type;
        }
        
        $name = isset($this->name) ? " {$this->name} from" : '';
        
        $message = "Unable to cast" . $name . " $valueType to $type" . (isset($explain) ? ": $explain" : '');
        
        throw new TypeCastException($message);
    }
}
