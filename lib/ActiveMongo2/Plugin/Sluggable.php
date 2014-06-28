<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2013 ActiveMongo                                                  |
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
 *  @Plugin(Sluggable)
 */
class Sluggable
{
    protected static function cleanText($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        return preg_replace('~[^-\w]+~', '', $text);
    }
    public static function sluggify($text)
    {
        $text = self::cleanText($text);
        if (empty($text)) {
            return 'n-a';
        }

        return substr($text, 0, 50);
    }

    protected static function check($args)
    {
        if (count($args) != 2) {
            throw new \RuntimeException("@Sluggable expects two arguments");
        }
    }

    /**
     *  @preUpdate
     */
    public static function updateSlugUrl($obj, Array $event_args, $conn, $args)
    {
        self::check($args);
        $source = $args[0];
        $target = $args[1];

        $document = &$event_args[0];
        if (empty($obj->$target)) {
            // Rarely use case
            // @Sluggable has been added and old documents are being update
            $slug = self::sluggify($obj->$source ?: 'n-a');
            $col  = $conn->getCollection($obj);

            while ( $col->count(array($target => $slug)) != 0) {
                $slug .= '-' . uniqid(true);
            }

            $document['$set'][$target] = $slug;
        }
    }

    /**
     *  @preCreate
     */
    public static function setSlugUrl($obj, Array $event_args, $conn, $args)
    {
        self::check($args);

        $document = &$event_args[0];
        if (!empty($document[$args[1]])) {
            $slug = self::sluggify($document[$args[1]]);
        } else{
            $slug = self::sluggify(empty($document[$args[0]]) ? 'n-a' : $document[$args[0]]);
        }
        $col  = $conn->getCollection($obj);

        while ( $col->count(array($args[1] => $slug)) != 0) {
            $slug .= '-' . uniqid(true);
        }

        $document[$args[1]] = $slug;
    }
}
