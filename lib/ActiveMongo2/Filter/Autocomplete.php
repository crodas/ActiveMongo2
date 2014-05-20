<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2014 ActiveMongo                                                  |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
*/
namespace ActiveMongo2\Plugin;

/**
 *  @Plugin(Autocomplete)
 */
class Autocomplete
{
    /**
     *  @onMapping
     */
    public static function onCompile($schema)
    {
        $schema->defineProperty('/** @Array @Index */', '__index_autocomplete');
    }

    /**
     *  @preSave
     */
    public static function onUpdate($obj, $args, $conn, $ann, $mapper)
    {
        $reflection = $mapper->getReflection(get_class($obj));
        $text = [];
        foreach ($reflection->properties('@Autocomplete') as $property) {
            $text[] = $property->get($obj);
        }

        $text   = implode("\n", $text);
        $text   = mb_strtolower(trim($text));
        if (preg_match('/\W+/', $text)) {
            $words  = array_merge([$text], preg_split('/\W+/', $text));
        } else {
            $words  = [$text];
        }
        $ngrams = [];
        foreach ($words as $word) {
            $len  = mb_strlen($word);
            for ($e = 1; $e <= $len; $e++) {
                $ngrams[] = mb_substr($word, 0, $e);
            }
        }

        $obj->__index_autocomplete = $ngrams;
    }

    /**
     *  @onQuery
     */
    public static function AutocompleteQuery(&$query)
    {
        if (empty($query['$autocomplete'])) {
            return;
        }
        
        $query['__index_autocomplete'] = mb_strtolower($query['$autocomplete']);
        unset($query['$autocomplete']);
    }
}
