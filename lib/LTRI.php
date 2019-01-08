<?php

class LTRI
{
    /**
     * Get the uuid from the ltri (without params)
     * 
     * @param string $id
     * @return string
     */
    public static function getUUID($id)
    {
        $id = (string)$id;
        
        if (strpos($id, 'lt:') !== false) {
            $id = preg_replace('/^lt:(.*)\//', '', $id);
        }
        
        if (strpos($id, '?') === false) {
            return $id;
        }
        
        $uuid = substr($id, 0, strpos($id, '?'));
        
        return $uuid;
    }
    
    /**
     * Get the version from the ltri params
     * 
     * @param string $id
     * @return string|null
     */
    public static function getVersion($id)
    {
        $id = (string)$id;
        
        $query = substr($id, strpos($id, '?') + 1);
        parse_str($query, $params);
        
        if (isset($params) && isset($params['v'])) {
            return $params['v'];
        }
    }
}
