<?php
namespace ajf\GGON;

require_once '../vendor/autoload.php';

class Encoder
{
    // Encodes a array or string to a GGON (Gang Garrison Object Notation) text
    // Encoding is recursive (arrays within arrays will be encoded)
    // If $numberToString is set to true, then ints and floats will be encoded as strings
    // Returns the encoded text
    public static function encode($value, $numberToString = false)
    {
        if (is_string($value)) {
            // Check if alphanumeric
            // We don't set alphanumeric to true ahead of time because of empty strings
            $alphanumeric = false;
            for ($i = 0; $i < strlen($value); $i++) {
                $char = $value[$i];
                if (('a' <= $char && $char <= 'z') || ('A' <= $char && $char <= 'Z') || ('0' <= $char && $char <= '9') || $char === '_' or $char === '.' or $char === '+' or $char === '-') {
                    $alphanumeric = true;
                } else {
                    $alphanumeric = false;
                    break;
                }
            }
            
            // As no quoting is necessary, just output verbatim
            if ($alphanumeric) {
                return $value;
            }
            
            $out = "'";
            for ($i = 0; $i < strlen($value); $i++) {
                $char = $value[$i];
                // ' and \ are escaped as \' and ''
                if ($char === "'" || $char === "\\") {
                    $out .= "\\$char";
                // newlines, carriage returns, tabs and null bytes are escaped specially
                } else if ($char === "\n") {
                    $out .= '\n';
                } else if ($char === "\r") {
                    $out .= '\r';
                } else if ($char === "\t") {
                    $out .= '\t';
                } else if ($char === "\x00") {
                    $out .= '\0';
                // Otherwise we can just output verbatim
                } else {
                    $out .= $char;
                }
            }
            $out .= "'";
            
            return $out;
        } else if (is_array($value)) {
            $out = "{";
            
            $first = true;
            foreach ($value as $map_key => $map_value) {
                if (!$first) {
                    $out .= ',';
                } else {
                    $first = false;
                }
                // As a numeric string key would become an int, we must set $numberToString to true
                $out .= self::encode($map_key, true) . ':' . self::encode($map_value, $numberToString);
            }
            
            $out .= "}";
            
            return $out;
        } else if ($numberToString && (is_int($value) || is_float($value))) {
            return self::encode(strval($value));
        } else {
            throw new GGONParseException("Cannot encode value of type " . gettype($value));
        }
    }
}