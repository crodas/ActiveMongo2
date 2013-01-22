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
namespace ActiveMongo2\Runtime\Validate;

use ActiveMongo2\Runtime\Utils;
use ActiveMongo2\Runtime\Serialize;
use ActiveMongo2\Runtime\Reference as ref;

class ReferenceMany
{
    public static function validate($value, $ann, $connection)
    {
        if (empty($value)) {
            return true;
        }

        if (!is_array($value)) {
            return false;
        }

        $class = current($ann['args']);
        $class = $connection->getDocumentClass($class);

        $ref = Utils::getReflectionClass($class);
        $ann = $ref->getAnnotations();
        if (!$ann->get('Referenceable')) {
            throw new \RuntimeException("$class can not be referenced");
        }

        foreach ($value as $id => $doc) {
            if ($doc instanceof ref) {
                continue;
            }
            if (!($doc instanceof $class)) {
                return false;
            }
        }

        return true;
    }

    public static function transformate($value, $ann, $connection)
    {
        if (empty($value)) {
            return NULL;
        }

        $values = array();
        $class  = current($ann['args']);
        $class  = $connection->getDocumentClass($class);

        $ref = Utils::getReflectionClass($class);
        $ann = $ref->getAnnotations();
        if (!$ann->get('Referenceable')) {
            throw new \RuntimeException("$class can not be referenced");
        }
        
        foreach ($value as $id => $doc) {
            if ($doc instanceof ref) {
                if (!$doc->getObject()) {
                    $values[$id] = $doc->getReference();
                    continue;
                }
                $doc = $doc->getObject();
            }

            if (!is_a($doc, $class)) {
                throw new \RuntimeException("Subdocument {$id} is not a valid object of $class");
            }

            $connection->save($doc);

            $raw = $connection->getRawDocument($doc);
            $ref = array(
                '$ref' => Serialize::getCollection($doc),
                '$id'  => $raw['_id'],
            );

            if ($ann->getOne('Referenceable')) {
                $keys = $ann->getOne('Referenceable');
                $keys = array_combine($keys, $keys);

                $ref['_extra'] = array_intersect_key($raw, $keys);
            }

            $values[$id] = $ref;
        }

        return $values;
    }

}
