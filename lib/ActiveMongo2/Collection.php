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

    public function update($filter, $update, $opts = array())
    {
        $this->analizeUpdate($update);
        if (empty($opts['w'])) {
            $opts['w'] = $this->config->getWriteConcern();
        }
        $opts = array_merge(self::$defaultOpts, $opts);
        return $this->zcol->update($filter, $update, $opts);
    }

    public function remove($filter, $opts = array())
    {
        if (empty($opts['w'])) {
            $opts['w'] = $this->config->getWriteConcern();
        }
        $opts = array_merge(self::$defaultOpts, $opts);
        $this->mapper->onQuery($this->zclass, $filter);
        return $this->zcol->remove($filter, $opts);
    }

    public function sum($key, $filter = array())
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

    public function count($filter = array(), $skip = 0, $limit = 0)
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
        $document = call_user_func_array([$this->zcol, 'aggregate'], func_get_args());
        if (empty($document['ok'])) {
            throw new \RuntimeException($document['errmsg']);
        }

        $results = [];
        foreach ($document['result'] as $res) {
            $results[] = $this->zconn->registerDocument($this->mapper->getObjectClass($this->zcol, $res), $res);
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

        return $this->zconn->registerDocument($this->mapper->getObjectClass($this->zcol, $response), $response);
    }

    public function find($query = array(), $fields = array())
    {
        $this->mapper->onQuery($this->zclass, $query);
        return new Cursor\Cursor($query, $fields, $this->zconn, $this->zcol, $this->mapper);
    }

    public function getById($id)
    {
        $cache = $this->cache->get([$this->zcol, $id]);
        if (is_array($cache)) {
            return $this->zconn->registerDocument(
                $this->mapper->getObjectClass($this->zcol, $cache), 
                $cache
            );
        }
        $document = $this->findOne(['_id' => $id]);

        if (!$document) {
            throw new \RuntimeException("Cannot find object with _id $id");
        }

        $this->cache->set([$this->zcol, $id], $this->mapper->getRawDocument($document, false));

        return $document;
    }

    public function resultCache(Array $object)
    {
        return new Cursor\Cache($object, $this->zconn, $this->zcol, $this->mapper);
    }

    public function findOne($query = array(), $fields = array())
    {
        $this->mapper->onQuery($this->zclass, $query);
        $doc =  $this->zcol->findOne($query, $fields);
        if (empty($doc)) {
            return $doc;
        }

        return $this->zconn->registerDocument($this->mapper->getObjectClass($this->zcol, $doc), $doc);
    }
}
