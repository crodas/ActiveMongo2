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

use slugifier as s;

/**
 *  @Plugin(Sluggable)
 *  @Plugin(Slugable)
 *  @Last
 */
class Slugable
{
    public static function slugify($text)
    {
        $text = s\slugify($text);
        if (empty($text)) {
            return 'n-a';
        }

        return substr($text, 0, 50);
    }

    protected static function check($args)
    {
        if (count($args) != 2) {
            throw new \RuntimeException("@Slugable expects two arguments");
        }
    }

    /**
     *  @preUpdate
     */
    public static function updateSlugUrl($obj, Array $event_args, $conn, $args, $mapper)
    {
        self::check($args);
        $source = self::text($mapper->getDocument($obj), $args[0]);
        $target = $args[1];

        $document = &$event_args[0];
        if (!empty($document['$set']) && !empty($document['$set'][$target])) {
            // slug has been updated, rebuild it!
            $source = $obj->$target;
            $obj->$target = null;
        }

        if (empty($obj->$target)) {
            // Rarely use case
            // @Slugable has been added and old documents are being update
            $slug = self::slugify($source);
            $document['$set'][$target] = self::checkSlug($conn, $obj, $target, $slug);
        }

    } 
    
    public static function text(Array $document, $field)
    {
        if (is_array($field)) {
            $text = [];
            foreach ($field as $f) {
                $text[] = $document[$f];
            }

            return implode('-', array_filter($text));
        } 
        
        return !empty($document[$field]) ? $document[$field] : '';
    }

    protected static function checkSlug($conn, $obj, $cname, $slug)
    {
        $col  = $conn->getCollection($obj);
        do {
            if (is_callable(array($obj, 'slugDupls'))) {
                $slug = $this->slugDups($cname, $slug);
            } else {
                $slug .= '-' . uniqid(true);
            }
        } while ($col->count(array($cname => $slug)) > 0);

        return $slug;
    }

    /**
     *  @preCreate
     */
    public static function setSlugUrl($obj, Array $event_args, $conn, $args)
    {
        self::check($args);

        $document = &$event_args[0];
        if (!empty($document[$args[1]])) {
            $slug = self::slugify($document[$args[1]]);
        } else{
            $slug = self::slugify(self::text($document, $args[0]) ?: 'n-a');
        }

        $document[$args[1]] = self::checkSlug($conn, $obj, $args[1], $slug);
    }
}
