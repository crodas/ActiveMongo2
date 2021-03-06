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

use Notoj\Annotation;
use ActiveMongo2\Runtime\Utils;

/** @Persist(collection="universal") */
class UniversalDocument
{
    /** @Id */
    public $id;

    /** @Reference */
    public $object;

}

/**
 *  @Plugin(Universal)
 */
class Universal
{
    /**
     *  @preCreate
     */
    public static function createId($doc, Array &$args, $conn, $annotation_args, $mapper)
    {
        if (!empty($annotation_args['set_id']) && !empty($annotation_args['auto_increment'])) {
            $args[0]['_id'] = Autoincrement::getId($conn, __NAMESPACE__ . "\\UniversalDocument");
        }
        return true;
    }

    /**
     *  @postCreate
     */
    public static function postCreateId($doc, Array $args, $conn, $annotation_args, $mapper)
    {
        $uuid = new UniversalDocument;
        $uuid->object = $doc;

        if (!empty($annotation_args['set_id'])) {
            $uuid->id = $args[0]['_id'];
        } else if (!empty($annotation_args['auto_increment'])) {
            $uuid->id = Autoincrement::getId($conn, get_class($uuid));
        }

        $conn->save($uuid);

        $mapper->updateProperty($doc, '@Universal', $uuid->id);

        $conn->save($doc);
    }
}
