<?php
namespace ajf\GGON;

require_once '../vendor/autoload.php';

class EncoderUtils
{
    // Takes an array
    // Returns an array which has a "length" key and a string key for each index
    // This is a function to make dealing with GGON easier, as GGON lacks lists
    // This is equivalent to the GML function ggon_list_to_map
    public static function listToMap(array $list)
    {
        $map = [];
        $map["length"] = count($list);
    
        for ($i = 0; $i < count($list); $i++) {
            $map[(string)$i] = $list[$i];
        }
        
        return $map;
    }
    
    // Takes an array which has a "length" key and string indexes up to length
    // Returns a numerically-indexed array which has the values of those indexes in order
    // This is a function to make dealing with GGON easier, as GGON lacks lists
    // This is designed to work with the array ggon_list_to_map/listToMap would produce
    // This is equivalent to the GML function ggon_map_to_list
    public static function mapToList(array $map)
    {
        $list = [];
        $length = (int)$map['length'];
        
        for ($i = 0; $i < $length; $i++) {
            $list[] = $map[(string)$i];
        }
        
        return $list;
    }
}