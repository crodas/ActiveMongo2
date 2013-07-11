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
namespace ActiveMongo2;

use MongoCollection;
use ActiveMongo2\Runtime\Utils;

class Collection 
{
    protected $zconn;
    protected $class;
    protected $zcol;

    protected static $defaultOpts = array(
        'w' => 1,
        'multiple' => true,
    );

    public function __construct(Connection $conn, $class, MongoCollection $col)
    {
        if (!Utils::class_exists($class)) {
            throw new \RuntimeException("Cannot find {$class} class");
        }
        $this->zconn  = $conn;
        $this->zcol   = $col;
        $this->class  = $class;
    }

    protected function analizeUpdate($query)
    {
        $ref = Utils::getReflectionClass($this->class);
    }

    public function query()
    {
        return new FluentQuery($this);
    }

    public function update($filter, $update, $opts = array())
    {
        $this->analizeUpdate($update);
        $opts = array_merge(self::$defaultOpts, $opts);
        return $this->zcol->update($filter, $update, $opts);
    }

    public function ensureIndex()
    {
        $map = Runtime\Serialize::getDocummentMapping($this->class);
        $ref = Utils::getReflectionClass($this->class);
        
        foreach ($map as $property => $doc) {
            $prop = $ref->getProperty($property)->getAnnotations();
            if ($prop->has('Index')) {
                $this->zcol->ensureIndex(array($doc => 1));
            }
            if ($prop->has('Unique')) {
                $this->zcol->ensureIndex(array($doc => 1), array('unique' => true));
            }
        }

        return $this;
    }

    public function count($filter = array(), $skip = 0, $limit = 0)
    {
        return $this->zcol->count($filter, $skip, $limit);
    }

    public function drop()
    {
        $this->zcol->drop();
    }

    public function findAndModify($query, $update, $options)
    {
        $response = $this->zcol->findAndModify($query, $update, null, $options);

        return $this->zconn->registerDocument($this->class, $response);
    }

    public function find($query = array(), $fields = array())
    {
        return new Cursor($query, $fields, $this->zconn, $this->zcol, $this->class);
    }

    public function findOne($query = array(), $fields = array())
    {
        $doc =  $this->zcol->findOne($query, $fields);
        if (empty($doc)) {
            return $doc;
        }

        return $this->zconn->registerDocument($this->class, $doc);
    }
}
