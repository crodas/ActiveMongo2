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

use MongoId;
use MongoCollection;
use IteratorAggregate;

class Collection implements IteratorAggregate
{
    protected $zconn;
    protected $mapper;
    protected $zcol;
    protected $cache;
    protected $zclass;

    protected static $defaultOpts = array(
        'multiple' => true,
        'w'        => 0,
    );

    public function __construct(Connection $conn, $mapper, MongoCollection $col, $cache, $config, $name)
    {
        $this->config = $config;
        $this->cache  = $cache;
        $this->zconn  = $conn;
        $this->zcol   = $col;
        $this->mapper = $mapper;
        $this->zclass = $name;
    }

    public function getIterator()
    {
        return $this->find([]);
    }

    public function getReflection()
    {
        return $this->mapper->getReflection($this->zclass);
    }


    public function rawCollection()
    {
        return $this->zcol;
    }

    protected function analizeUpdate($query)
    {
    }

    public function query()
    {
        return new FluentQuery($this);
    }

    public function populateFromArray($object, Array $data)
    {
        $this->mapper->populateFromArray($object, $data);
        return $object;
    }


    public function registerDocument($document)
    {
        $class = $this->mapper->getObjectClass($this->zcol, $document);
        $doc   = new $class;
        $this->mapper->populate($doc, $document);
        $this->mapper->trigger(true, 'onHydratation', $doc);

        return $doc;
    }


    public function update($filter, $update, $opts = [])
    {
        $this->mapper->onQuery($this->zclass, $filter);
        $this->analizeUpdate($update);
        $opts = array_merge(self::$defaultOpts, $opts);
        $opts['w'] = $this->config->getWriteConcern($opts['w']);
        return $this->zcol->update($filter, $update, $opts);
    }

    public function remove($filter, $opts = [])
    {
        $opts = array_merge(self::$defaultOpts, $opts);
        $opts['w'] = $this->config->getWriteConcern($opts['w']);
        $this->mapper->onQuery($this->zclass, $filter);
        return $this->zcol->remove($filter, $opts);
    }

    public function sum($key, $filter = [])
    {
        $this->mapper->onQuery($this->zclass, $filter);
        $object = $this->zcol->aggregate([
            ['$match' => $filter],
            ['$group' => [
                '_id' => null,
                'sum' => ['$sum' => '$' . $key],
            ]],
        ]);
        
        if (empty($object['result'][0])) {
            return 0;
        }
        
        return $object['result'][0]['sum'];
    }

    public function count($filter = [], $skip = 0, $limit = 0)
    {
        $this->mapper->onQuery($this->zclass, $filter);
        return $this->zcol->count($filter, $skip, $limit);
    }

    public function drop()
    {
        $this->zcol->drop();
    }

    public function aggregate()
    {
        $aggregate = func_get_args(); 
        if (count($aggregate) == 1 && is_array($aggregate[0])) {
            $aggregate = $aggregate[0];
        }

        $document  = $this->zcol->aggregate($aggregate);
        if (empty($document['ok'])) {
            throw new \RuntimeException($document['errmsg']);
        }

        $results = [];
        foreach ($document['result'] as $res) {
            try {
                $results[] = $this->registerDocument($res);
            } catch (\RuntimeException $e) {
                return $document['result'];
            }
        }

        return $results;
    }

    public function findAndModify($query, $update, $options = [])
    {
        $this->mapper->onQuery($this->zclass, $query);
        $response = $this->zcol->findAndModify($query, $update, null, $options);

        if (empty($response)) {
            return NULL;
        }

        return $this->registerDocument($response);
    }

    /**
     *  Search in the database with the $query criteria and return the required $fields.
     *
     *  @param $query MongoDB's query syntax
     *  @param $fields Fields to return
     *
     *  @return ActiveMongo2\Cursor\Cursor
     */
    public function find($query = [], $fields = [])
    {
        $this->mapper->onQuery($this->zclass, $query);
        return new Cursor\Cursor($query, $fields, $this->zconn, $this, $this->zcol, $this->mapper);
    }

    /**
     *  Get() is similar to find() but it throws an exception if query has
     *  zero matches.
     *
     *  @param $query MongoDB's query syntax
     *  @param $fields Fields to return
     *
     *  @return ActiveMongo2\Cursor\Cursor
     */
    public function get($query = [], $fields = [])
    {
        $this->mapper->onQuery($this->zclass, $query);
        $cursor = new Cursor\Cursor($query, $fields, $this->zconn, $this, $this->zcol, $this->mapper);
        
        if ($cursor->count() === 0) {
            throw new Exception\NotFound;
        }

        return $cursor;
    }

    /**
     *  Make sure the parameter looks like a document Id.
     *
     *  @param mixed $id
     *  @return _id
     */
    protected function getIdQuery($id)
    {
        if (is_string($id) && is_numeric($id)) {
            return ['$in' => [$id, $id+0]];
        }
        if (is_string($id) && preg_match("/^[a-f0-9]{24}$/i", $id)) {
            return ['$in' => [$id, new MongoId($id)]];
        }

        return $id;
    }

    /**
     *  Search the document with {_id: $id} and returns the value. It will thrown a NotFound Exception
     *  if the search has no records.
     *
     *  @param mixed $id    Document Id
     *  @return object
     */
    public function getById($id)
    {
        $cache = $this->cache->get([$this->zcol, $id]);
        if (is_array($cache) && !empty($cache)) {
            return $this->registerDocument(
                $cache
            );
        }
        $document = $this->findOne(['_id' => $this->getIdQuery($id)]);

        if (!$document) {
            throw new Exception\NotFound("Cannot find object with _id $id");
        }

        $this->cache->set([$this->zcol, $id], $this->mapper->getRawDocument($document, false));

        return $document;
    }

    public function is($object)
    {
        if ($object instanceof Reference) {
            return $this->zclass == $object->getClass();
        }
        return $object instanceof $this->zclass;
    }


    public function getReference($object, $cache = [])
    {
        return $this->mapper->getReference($object, array_flip($cache));
    }


    public function resultCache(Array $object)
    {
        return new Cursor\Cache($object, $this->zconn, $this, $this->zcol, $this->mapper);
    }

    /**
     *  Similar to `findOne` but it will thrown an exception if the query has no result.
     *
     *  @param $query MongoDB's query syntax
     *  @param $fields Fields to return
     *
     *  @return document
     */
    public function getOne($query = [], $fields = [])
    {
        $this->mapper->onQuery($this->zclass, $query);
        $doc = $this->zcol->findOne($query, $fields);
        if (empty($doc)) {
            throw new Exception\NotFound;
        }

        return $this->registerDocument($doc);
    }

    /**
     *  Similar to `find` but it returns the first document or null instead of a Cursor
     *
     *  @param $query MongoDB's query syntax
     *  @param $fields Fields to return
     *
     *  @return document
     */
    public function findOne($query = [], $fields = [])
    {
        $this->mapper->onQuery($this->zclass, $query);
        $doc = $this->zcol->findOne($query, $fields);
        if (empty($doc)) {
            return $doc;
        }

        return $this->registerDocument($doc);
    }
}
