<?php
namespace ajf\GGON;

use \SplQueue as Queue;

class GGONParseException extends \Exception
{
}

class Parser
{
    // Decodes a GGON (Gang Garrison Object Notation) text
    // Returns either a string or an array
    public static function parse($text)
    {
        $tokens = self::tokenise($text);
        return self::parseTokens($tokens);
    }
    
    // Decodes a tokenised GGON (Gang Garrison Object Notation) text SplQueue
    // Returns either a string or an array
    private static function parseTokens($tokens)
    {
        while (!$tokens->isEmpty()) {
            $token = $tokens->dequeue();
            
            // String
            if ($token[0] == '%') {
                return substr($token, 1);
            }
            
            // GGON has only two primitives - it could only be string or opening {
            if ($token !== '{') {
                throw new GGONParseException("Unexpected token \"$token\"");
            }
                
            $array = [];
            
            $token = $tokens->bottom();
            if ($token === "}") {
                $tokens->dequeue();

                // It's {} so we can just return our empty array
                return $array;
            } else if ($token[0] == '%') {
                // Parse each key of our map
                while (!$tokens->isEmpty()) {
                    $token = $tokens->dequeue();
                    
                    $key = substr($token, 1);
                    
                    $token = $tokens->dequeue();
                    
                    // Following token must be a : as we have a key
                    if ($token !== ":") {
                        throw new GGONParseException("Unexpected token \"$token\" after key");
                    }
                    
                    // Now we recurse to parse our value!
                    $value = self::parseTokens($tokens);
                    
                    $array[$key] = $value;
                    
                    $token = $tokens->dequeue();
                    
                    // After key, colon and value, next token must be , or }
                    if ($token === ',') {
                        continue;
                    } else if ($token === '}') {
                        return $array;
                    } else {
                        throw new GGONParseException("Unexpected token \"$token\" after value");
                    }
                }
            } else {
                throw new GGONParseException("Unknown token \"$token\"");
            }
        }
    }
    
    // Tokenises a GGON (Gang Garrison Object Notation) text
    // Returns an SplQueue of tokens
    // For each token, list has type ("punctuation" or "string") and value
    // Thus you must iterate over the list two elements at a time
    private static function tokenise($text)
    {
        $i = 0;
        $len = strlen($text);
        $tokens = new Queue();
        
        while ($i < $len) {
            $char = $text[$i];
            
            // basic punctuation: '{', '}', ':' and ','
            if ($char === '{' || $char === '}' || $char === ':' || $char === ',') {
                $tokens->enqueue($char);
                $i += 1;
                continue;
            }
            
            // skip whitespace (space, tab, new line || carriage return)
            if ($char === " " || $char === "\t" || $char === "\n" || $char == "\r") {
                $i += 1;
                continue;
            }
            
            // "identifiers" (bare word strings, really) of format [a-zA-Z0-9_]+
            if (('a' <= $char && $char <= 'z') || ('A' <= $char && $char <= 'Z') || ('0' <= $char && $char <= '9') || $char === '_' || $char === '.' || $char === '+' || $char === '-') {
                $identifier = '';
                while (('a' <= $char && $char <= 'z') || ('A' <= $char && $char <= 'Z') || ('0' <= $char && $char <= '9') || $char === '_' || $char === '.' || $char === '+' || $char === '-') {
                    $identifier .= $char;
                    $i += 1;
                    if ($i >= $len) {
                        break;
                    }
                    $char = $text[$i];
                }
                $tokens->enqueue('%' . $identifier);
                continue;
            }
            
            // string
            if ($char === "'") {
                $str = "";
                $i += 1;
                $char = $text[$i];
                while ($char !== "'") {
                    if ($i > $len) {
                        throw new GGONParseException('Unexpected end of text while parsing string');
                    }
                    // escaping
                    if ($char == "\\") {
                        $i += 1;
                        $char = $text[$i];
                        if ($char === "'" || $char === "\\") {
                            $str .= $char;
                        } else if ($char === 'n') {
                            $str .= "\n";
                        } else if ($char === 'r') {
                            $str .= "\r";
                        } else if ($char === 't') {
                            $str .= "\t";
                        } else if ($char === '0') {
                            $str .= "\x00";
                        } else {
                            throw new GGONParseException("Unknown escape sequence \"\\$char\"");
                        }
                    } else {
                        $str .= $char;
                    }
                    $i += 1;
                    $char = $text[$i];
                }
                if ($char !== "'") {
                    throw new GGONParseException("Unexpected character \"$char\" while parsing string, expected \"'\"");
                }
                $i += 1;
                
                $tokens->enqueue('%' . $str);
                continue;
            }
            
            throw new GGONParseException("Unexpected character \"$char\"");
        }
        
        return $tokens;
    }
}
