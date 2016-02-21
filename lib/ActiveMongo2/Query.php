<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2015 ActiveMongo                                                  |
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

trait Query
{
    protected static $conn;

    public static function find_or_create_by(Array $object)
    {
        return static::findOrCreateBy($object);
    }

    public static function findOrCreateBy(Array $object)
    {
        $col = static::$conn->getCollection(__CLASS__);
        $doc = $col->findOne($object);
        if (empty($doc)) {
            $doc = new static;
            $col->populateFromArray($doc, $object);
        }

        return $doc;
    }

    public static function setConnection(Connection $conn)
    {
        static::$conn = $conn;
    }

    public static function pluck()
    {
        $fields = func_Get_args();
        $rows   = [];
        foreach (static::$conn->getCollection(__CLASS__)->rawCollection()->find([], $fields) as $row) {
            if (count($fields) == 1) {
                $rows[] = $row[$fields[0]];
            } else {
                $rows[] = $row;
            }
        }
        return $rows;
    }


    public static function getOne(Array $filter = [], Array $fields = [])
    {
        return static::$conn->getCollection(__CLASS__)->getOne($filter, $fields);
    }

    public static function get(Array $filter = [], Array $fields = [])
    {
        return static::$conn->getCollection(__CLASS__)->get($filter, $fields);
    }

    public static function findOne(Array $filter = [], Array $fields = [])
    {
        return static::$conn->getCollection(__CLASS__)->findOne($filter, $fields);
    }

    public static function find(Array $filter = [], Array $fields = [])
    {
        return static::$conn->getCollection(__CLASS__)->find($filter, $fields);
    }

    public static function where(Array $filter = [], Array $fields = [])
    {
        return static::$conn->getCollection(__CLASS__)->find($filter, $fields);
    }

    public static function byId($id)
    {
        return static::getById($id);
    }

    public static function getById($id)
    {
        if (is_array($id)) {
            $cursor = static::$conn->getCollection(__CLASS__)->find(['_id' => ['$in' => $id]]);
            if ($cursor->count() != count($id)) {
                throw new Exception\NotFound("Cannot find all elements");
            }
            return $cursor;
        }
        return static::$conn->getCollection(__CLASS__)->getById($id);
    }

    public static function sum($field, $where = [])
    {
        return static::$conn->getCollection(__CLASS__)->sum($field, $where);
    }

    public function save()
    {
        return static::$conn->save($this);
    }
}
