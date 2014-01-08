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

    public function __construct(Configuration $config, MongoClient $conn, $db)
    {
        $this->config = $config;
        $this->cache  = $config->getCache();
        $this->mapper = $config->initialize($this);
        $this->conn   = $conn;
        $this->db     = $conn->selectDB($db);
    }

    public function setCacheStorage(Cache\Storage $storage)
    {
        $this->cache->setStorage($storage);
        return $this;
    }

    public function command($command, $args = array())
    {
        return $this->db->command($command, $args);
    }

    public function getDatabase()
    {
        return $this->db;
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function __get($name)
    {
        return $this->getCollection($name);
    }

    public function getDocumentClass($collection)
    {
        return $this->mapper->mapCollection($collection)['class'];
    }

    public function getCollection($collection)
    {
        list($col, $class) = $this->mapper->getCollectionObject($collection, $this->db);

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

    public function registerDocument($class, $document)
    {
        $doc = new $class;
        $this->setObjectDocument($doc, $document);
        $this->mapper->trigger('onHydratation', $doc);

        return $doc;
    }

    protected function setObjectDocument(&$object, $document)
    {
        $this->mapper->populate($object, $document);
    }

    public function delete($obj, $w = null)
    {
        if ($w === null) $w = $this->config->getWriteConcern();
        $col = $this->getMongoCollection($obj);

        $document = $this->mapper->getDocument($obj);
        $hash = spl_object_hash($obj);
        if (empty($document['_id'])) {
            throw new \RuntimeException("Cannot delete without an id");
        }

        $this->mapper->trigger('preDelete', $obj, array($document));

        $col->remove(array('_id' => $document['_id']), compact('w'));

        $this->mapper->trigger('postDelete', $obj, array($document));
    }

    public function worker($daemon = true)
    {
        $worker = new Worker($this);
        $done   = 0;
        do {
            $done += $worker->main();
            usleep(200000);
        } while ($daemon);
        return $done;
    }

    public function getReference($object, $cache = [])
    {
        return $this->mapper->getReference($object, array_flip($cache));
    }

    public function is($collection, $object)
    {
        $class = $this->mapper->mapCollection($collection)['class'];
        if ($object instanceof Reference) {
            return $class == $object->getClass();
        }
        return $object instanceof $class;
    }

    public function file($obj)
    {
        $col = $this->getMongoCollection($obj);
        if ($obj instanceof DocumentProxy) {
            throw new \RuntimeException("Cannot update a reference");
        } else if (!$col instanceof \MongoGridFS) {
            throw new \RuntimeException("@GridFS Annotation missing");
        }
        $document = $this->mapper->validate($obj);
        $oldDoc   = $this->mapper->getRawDocument($obj, false);

        if (!empty($oldDoc)) {
            throw new \RuntimeException("Update on @GridFS is not yet implemented");
        }

        $this->mapper->trigger('preCreate', $obj, array(&$document, $this));
        $document['_id'] = empty($document['_id']) ?  new MongoId : $document['_id'];

        return new StoreFile($col, $document, $this, $obj);
    }

    protected function getMongoCollection($obj)
    {
        $class = $this->mapper->get_class($obj);
        list($col, $class) = $this->mapper->getCollectionObject($class, $this->db);

        return $col;
    }

    protected function create(&$obj, $document, $col, $trigger_events)
    {
        $trigger_events && $this->mapper->trigger('preCreate', $obj, array(&$document, $this));

        $document['_id'] = empty($document['_id']) ?  new MongoId : $document['_id'];

        $this->setObjectDocument($obj, $document);

        $ret = $col->save($document, compact('w'));

        $trigger_events && $this->mapper->trigger('postCreate', $obj, array($document, $this));
        $trigger_events && $this->mapper->trigger('postSave', $obj, array($document, $this));

        return $this;
    }

    protected function update(&$obj, $document, $col, $oldDoc, $trigger_events, $w) {
        $update = $this->mapper->update($obj, $document, $oldDoc);

        if (empty($update)) {
            return $this;
        }

        $trigger_events && $this->mapper->trigger('preUpdate', $obj, array(&$update, $this));

        foreach ($update as $op => $value) {
            $col->update(
                array('_id' => $oldDoc['_id']), 
                array($op => $value),
                compact('w')
            );
        }

        $this->setObjectDocument($obj, $document);

        $trigger_events && $this->mapper->trigger('postUpdate', $obj, array($update, $this,$oldDoc['_id']));
        $trigger_events && $this->mapper->trigger('postSave', $obj, array($update, $this));

        return $this;
    }

    protected function handleSaveProxy($obj)
    {
        if ($obj instanceof DocumentProxy) {
            $obj = $obj->getObject();
            if (empty($obj)) {
                return true;
            }
        }
    }

    public function save(&$obj, $w = null, $trigger_events = true)
    {
        if ($this->handleSaveProxy($obj)) {
            return $this;
        }

        $w = $w ?: $this->config->getWriteConcern();

        $trigger_events && $this->mapper->trigger('preSave', $obj, array(&$update, $this));

        $col = $this->getMongoCollection($obj);
        if ($col instanceof \MongoGridFS) {
            throw new \RuntimeException("@GridFS must be saved with file");
        }

        $document = $this->mapper->validate($obj);
        $oldDoc   = $this->mapper->getRawDocument($obj, false);
        if ($oldDoc) {
            return $this->update($obj, $document, $col, $oldDoc, $trigger_events, $w);
        }

        return $this->create($obj, $document, $col, $trigger_events);
    }

    public function ensureIndex()
    {
        $this->mapper->ensureIndex($this->db);
    }

    public function dropDatabase()
    {
        return $this->db->drop();
    }
}
