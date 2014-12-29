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

use MongoClient;
use MongoId;

if (!is_callable('password_verify')) {
    require __DIR__ . "/Compat/password/password.php";
}

class Connection
{
    protected $conn;
    protected $db;

    /** 
     *  Collections to Classes mapping
     */
    protected $collections;

    protected $mapper;
    protected $cache;
    protected $config;
    protected $queue = array();

    public function __construct(Configuration $config, MongoClient $conn, $db)
    {
        $this->config = $config;
        $this->cache  = $config->getCache();
        $this->mapper = $config->initialize($this);
        $this->conn   = array('default' => $conn);
        $this->db     = array('default' => $conn->selectDB($db));
        $this->mapper->setDatabases($this->db);
        if ($config->hasGenerated() || $config->isDevel())  {
            $this->ensureIndex(true);
        }
    }

    public function addConnection($name, MongoClient $conn, $dbname)
    {
        $this->conn[$name] = $conn;
        $this->db[$name]   = $conn->selectDB($dbname);
        $this->mapper->setDatabases($this->db);
        if ($this->config->hasGenerated() || $this->config->isDevel())  {
            $this->ensureIndex(true);
        }
        return $this;
    }

    public function setCacheStorage(Cache\Storage $storage)
    {
        $this->cache->setStorage($storage);
        return $this;
    }

    public function command($command, $args = array(), $name = '')
    {
        return $this->getDatabase($name)->command($command, $args);
    }

    public function getDatabases()
    {
        return $this->db;
    }

    public function getDatabase($name = 'default')
    {
        if (empty($this->db[$name])) {
            throw new \RuntimeException("Cannot find connection $name");
        }
        return $this->db[$name];
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function getConnection($name = 'default')
    {
        if (empty($this->conn[$name])) {
            throw new \RuntimeException("Cannot find connection named '$name'");
        }
        return $this->conn[$name];
    }

    public function __get($name)
    {
        return $this->getCollection($name);
    }

    public function getCollections()
    {
        $cols = array();
        foreach ($this->mapper->getCollections() as $class => $col) {
            $cols[] = $this->getCollection($class);
        }
        return $cols;
    }

    public function getCollection($collection)
    {
        list($col, $class) = $this->mapper->getCollectionObject($collection);

        if (!empty($this->collections[$class])) {
            return $this->collections[$class];
        }

        $cache = $this->cache;
        if ($col instanceof \MongoGridFS) {
            $cache = new Cache\Storage\None;
        }

        $this->collections[$class] = new Collection($this, $this->mapper, $col, $cache, $this->config, $class);

        return $this->collections[$class];
    }

    public function delete($obj, $w = null, $trigger_events = true)
    {
        $w = $this->config->getWriteConcern($w);
        $col = $this->getMongoCollection($obj);

        $document = $this->mapper->getDocument($obj);
        $hash = spl_object_hash($obj);
        if (empty($document['_id'])) {
            throw new \RuntimeException("Cannot delete without an id");
        }

        $this->mapper->trigger($trigger_events, 'preDelete', $obj, array($document));

        $col->remove(array('_id' => $document['_id']), compact('w'));

        $this->mapper->trigger($trigger_events, 'postDelete', $obj, array($document));
    }

    public function worker($forever = true)
    {
        return Worker::run($forever, $this);
    }

    public function file($obj)
    {
        $col = $this->getMongoCollection($obj, 'MongoCollection', '@GridFS Annotation is missing');
        if ($obj instanceof DocumentProxy) {
            throw new \RuntimeException("Cannot update a reference");
        }
        $document = $this->mapper->validate($obj);
        $oldDoc   = $this->mapper->getRawDocument($obj, false);

        if (!empty($oldDoc)) {
            throw new \RuntimeException("Update on @GridFS is not yet implemented");
        }

        $this->mapper->trigger(true, 'preCreate', $obj, array(&$document, $this));
        $document['_id'] = empty($document['_id']) ?  new MongoId : $document['_id'];

        return new StoreFile($col, $document, $this, $obj, $this->mapper);
    }

    public function getMapper()
    {
        return $this->mapper;
    }

    public function getReflection($name)
    {
        if (!is_string($name)) {
            $name = $this->mapper->get_class($name);
        }
        return $this->mapper->getReflection($name);
    }

    protected function getMongoCollection($obj, $class = null, $error = null)
    {
        $class = $this->mapper->get_class($obj);
        list($col, $class) = $this->mapper->getCollectionObject($class);

        if ($col instanceof $class) {
            throw new \RuntimeException($error);
        }

        return $col;
    }

    protected function create($obj, $document, $col, $trigger_events)
    {
        $this->mapper->trigger($trigger_events, 'preCreate', $obj, array(&$document, $this));

        $document['_id'] = empty($document['_id']) ?  new MongoId : $document['_id'];

        $this->mapper->populate($obj, $document);

        $ret = $col->save($document, compact('w'));

        $this->mapper->trigger($trigger_events, 'postCreate', $obj, array($document, $this));
        $this->mapper->trigger($trigger_events, 'postSave', $obj, array($document, $this));

        return $this;
    }

    protected function update($obj, $document, $col, $oldDoc, $trigger_events, $w)
    {
        if (!($update = $this->mapper->update($obj, $document, $oldDoc))) {
            return $this;
        }

        $this->mapper->trigger($trigger_events, 'preUpdate', $obj, array(&$update, $this));

        foreach ($update as $op => $value) {
            $col->update(array('_id' => $oldDoc['_id']),  array($op => $value), compact('w'));
        }

        if (!empty($update['$set'])) {
            $document = array_merge($document, $update['$set']);
        }

        $this->mapper->populate($obj, $document);
        $this->mapper->trigger($trigger_events, 'postUpdate', $obj, array($update, $this,$oldDoc['_id']));
        $this->mapper->trigger($trigger_events, 'postSave', $obj, array($update, $this));

        return $this;
    }

    protected function handleSaveProxy(&$obj)
    {
        if ($obj instanceof DocumentProxy) {
            $obj = $obj->getObject();
            if (empty($obj)) {
                return true;
            }
        }
    }

    public function save($obj, $w = null, $trigger_events = true)
    {
        if ($this->handleSaveProxy($obj)) {
            return $this;
        }

        $w = $this->config->getWriteConcern($w);

        $this->mapper->trigger($trigger_events, 'preSave', $obj, array(&$update, $this));

        $col = $this->getMongoCollection($obj, 'MongoGridFs', '@GridFS must be saved with file');

        $oldDoc = $this->mapper->getRawDocument($obj, false);
        $objId  = $this->mapper->get_class($obj) . '::' . ($oldDoc ? $oldDoc['_id'] : spl_object_hash($obj));
        if (!empty($this->queue[$objId])) return;

        $this->queue[$objId] = true;
        try {
            $document = $this->mapper->validate($obj);

            if ($oldDoc) {
                $return = $this->update($obj, $document, $col, $oldDoc, $trigger_events, $w);
            } else {
                $return = $this->create($obj, $document, $col, $trigger_events);
            }
        } catch (\Exception $e) {
            unset($this->queue[$objId]);
            throw $e;
        }
        unset($this->queue[$objId]);

        return $return;
    }

    public function ensureIndex($background = false)
    {
        $this->mapper->ensureIndex($background);
    }

    public function dropDatabase($name = 'default')
    {
        return $this->getDatabase($name)->drop();
    }
}
