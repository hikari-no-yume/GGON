<?php
namespace ajf\GGON;

use \SplQueue as Queue;

class GGONParseException extends \Exception
{
}

class Parser
{
    const TOKEN_PUNCTUATION = "punctuation";
    const TOKEN_STRING = "string";
    
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
            $tokenType = $tokens->dequeue();
            $tokenValue = $tokens->dequeue();
            
            if ($tokenType === self::TOKEN_STRING) {
                return $tokenValue;
            }
            
            if ($tokenType === self::TOKEN_PUNCTUATION) {
                // GGON has only two primitives - it could only be string or opening {
                if ($tokenValue !== "{") {
                    throw new GGONParseException("Unexpected token \"$tokenValue\"");
                }
                
                $array = [];
                
                $tokenType = $tokens->bottom();
                if ($tokenType === self::TOKEN_PUNCTUATION) {
                    $tokenType = $tokens->dequeue();
                    $tokenValue = $tokens->dequeue();
                    
                    // { can only be followed by } or a key
                    if ($tokenValue !== '{}') {
                        throw new GGONParseExceptuon("Unexpected token \"$tokenValue\" after opening \"{\"");
                    }
                    
                    // It's {} so we can just return our empty array
                    return $array;
                } else if ($tokenType === self::TOKEN_STRING) {
                    // Parse each key of our map
                    while (!$tokens->isEmpty()) {
                        $tokenType = $tokens->dequeue();
                        $tokenValue = $tokens->dequeue();
                        
                        $key = $tokenValue;
                        
                        $tokenType = $tokens->dequeue();
                        $tokenValue = $tokens->dequeue();
                        
                        // Following token must be a : as we have a key
                        if ($tokenType !== self::TOKEN_PUNCTUATION) {
                            throw new GGONParseException("Unexpected $tokenType after key");
                        }
                        if ($tokenValue !== ":") {
                            throw new GGONParseException("Unexpected token \"$tokenValue\" after key");
                        }
                        
                        // Now we recurse to parse our value!
                        $value = self::parseTokens($tokens);
                        
                        $array[$key] = $value;
                        
                        $tokenType = $tokens->dequeue();
                        $tokenValue = $tokens->dequeue();
                        
                        // After key, colon and value, next token must be , or }
                        if ($tokenType !== self::TOKEN_PUNCTUATION) {
                            throw new GGONParseException("Unexpected $tokenType after value");
                        }
                        if ($tokenValue === ',') {
                            continue;
                        } else if ($tokenValue === '}') {
                            return $array;
                        } else {
                            throw new GGONParseException("Unexpected token \"$tokenValue\" after value");
                        }
                    }
                } else {
                    throw new GGONParseException("Unknown token type \"$tokenType\"");
                }
            }
            
            throw new GGONParseException("Unknown token type \"$tokenType\"");
        }
    }
    
    // Tokenises a GGON (Gang Garrison Object Notation) text
    // Returns an SplQueue of tokens
    // For each token, list has type ("punctuation" or "string") and value
    // Thus you must iterate over the list two elements at a time
    private static function tokenise($text)
    {
        $tokens = new Queue();
        
        while (strlen($text) > 0) {
            $char = $text[0];
            
            // basic punctuation: '{', '}', ':' and ','
            if ($char === '{' || $char === '}' || $char === ':' || $char === ',') {
                $tokens->enqueue(self::TOKEN_PUNCTUATION);
                $tokens->enqueue($char);
                $text = substr($text, 1);
                continue;
            }
            
            // skip whitespace (space, tab, new line or carriage return)
            if ($char === " " || $char === "\t" || $char === "\n" || $char == "\r") {
                $text = substr($text, 1);
                continue;
            }
            
            // "identifiers" (bare word strings, really) of format [a-zA-Z0-9_]+
            if (('a' <= $char && $char <= 'z') || ('A' <= $char && $char <= 'Z') || ('0' <= $char && $char <= '9') || $char === '_' or $char === '.' or $char === '+' or $char === '-') {
                $identifier = '';
                while (('a' <= $char && $char <= 'z') || ('A' <= $char && $char <= 'Z') || ('0' <= $char && $char <= '9') || $char === '_' or $char === '.' or $char === '+' or $char === '-') {
                    if (strlen($text) === 0) {
                        throw new GGONParseException('Unexpected end of text while parsing string');
                    }
                    $identifier .= $char;
                    $text = substr($text, 1);
                    $char = $text[0];
                }
                $tokens->enqueue(self::TOKEN_STRING);
                $tokens->enqueue($identifier);
                continue;
            }
            
            // string
            if ($char === "'") {
                $str = "";
                $text = substr($text, 1);
                $char = $text[0];
                while ($char !== "'") {
                    if (strlen($text) === 0) {
                        throw new GGONParseException('Unexpected end of text while parsing string');
                    }
                    // escaping
                    if ($char == "\\") {
                        $text = substr($text, 1);
                        $char = $text[0];
                        if ($char === "'" or $char === "\\") {
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
                    $text = substr($text, 1);
                    $char = $text[0];
                }
                if ($char !== "'") {
                    throw new GGONParseException("Unexpected character \"$char\" while parsing string, expected \"'\"");
                }
                $text = substr($text, 1);
                $char = $text[0];
                
                $tokens->enqueue(self::TOKEN_STRING);
                $tokens->enqueue($str);
                continue;
            }
            
            throw new GGONParseException("Unexpected character \"$char\"");
        }
        
        return $tokens;
    }
}