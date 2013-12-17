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

    /**
     *  Classes to Collections mapping
     */
    protected $classes;
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

    /**
     *  Clone a document 
     *  
     *  Clones a document and removed internals variables
     *
     *  @return object
     */
    public function cloneDocument($doc)
    {
        $tmp = clone $doc;
        return $tmp;
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
        try {
            $data = $this->mapper->mapCollection($collection);
        } catch (\RuntimeException $e) {
            $data = $this->mapper->mapClass($collection);
        }

        if (!empty($this->collections[$data['class']])) {
            return $this->collections[$data['class']];
        }

        $cache = $this->cache;
        if (!empty($data['is_gridfs'])) {
            $mongoCol = $this->db->getGridFs($data['name']);
            $cache    = new Cache\Storage\None;
        } else {
            $mongoCol = $this->db->selectCollection($data['name']);
        }

        $this->collections[$data['class']] = new Collection($this, $this->mapper, $mongoCol, $cache, $this->config, $data['class']);

        return $this->collections[$data['class']];
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
        $class = $this->mapper->get_class($obj);
        if (empty($this->classes[$class])) {
            $collection = $this->mapper->mapClass($class)['name'];
            $this->classes[$class] = $this->db->selectCollection($collection);
        }

        $document = $this->mapper->getDocument($obj);
        $hash = spl_object_hash($obj);
        if (empty($document['_id'])) {
            throw new \RuntimeException("Cannot delete without an id");
        }

        $this->mapper->trigger('preDelete', $obj, array($document));

        $this->classes[$class]->remove(array('_id' => $document['_id']), compact('w'));

        $this->mapper->trigger('postDelete', $obj, array($document));
    }

    public function worker($daemon = true)
    {
        $queue = $this->db->deferred_queue;
        $refs  = $this->db->references_queue;

        $queue->ensureIndex(['processed' => 1]);
        $refs->ensureIndex(['source_id' => 1]);

        $done = 0;
        do {
            $work = $queue->findAndModify(
                ['processed' => false], 
                ['$set' => ['processed' => true, 'started' => new \MongoDate]], 
                null, 
                ['sort' => ['$natural' => -1]]
            );
            if (empty($work)) {
                if ($daemon) {
                    usleep(200000);
                    continue;
                }
                break;
            }
            $all  = $refs->find(['source_id' => $work['source_id']]);
            foreach ($all as $row) {
                $update = $work['update'];
                foreach ($update as $op => $fields) {
                    foreach ($fields as $field => $value) {
                        unset($update[$op][$field]);
                        $update[$op][$row['property'] . '.' . $field] = $value;
                    }
                }
                $col = $this->db->{$row['collection']};
                $col->update(
                    ['_id' => $row['id']],
                    $update
                );
                $done++;
            }
            $queue->remove(['_id' => $work['_id']]);
        } while (true);
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
        if ($obj instanceof DocumentProxy) {
            throw new \RuntimeException("Cannot update a reference");
        }
        $data = $this->mapper->mapClass($this->mapper->get_class($obj));;
        if (!$data['is_gridfs']) {
            throw new \RuntimeException("Missing @GridFS argument");
        }
        $col      = $this->db->getGridFs($data['name']);
        $document = $this->mapper->validate($obj);
        $oldDoc   = $this->mapper->getRawDocument($obj, false);

        if (!empty($oldDoc)) {
            throw new \RuntimeException("Update on @GridFS is not yet implemented");
        }

        $this->mapper->trigger('preCreate', $obj, array(&$document, $this));
        if (empty($document['_id'])) {
            $document['_id'] = new MongoId;
        }

        return new StoreFile($col, $document, $this, $obj);
    }

    public function save(&$obj, $w = null, $trigger_events = true)
    {
        if ($w === null) $w = $this->config->getWriteConcern();
        if ($obj instanceof DocumentProxy) {
            $obj = $obj->getObject();
            if (empty($obj)) {
                return $this;
            }
        }

        if ($trigger_events) {
            $this->mapper->trigger('preSave', $obj, array(&$update, $this));
        }

        $class = $this->mapper->get_class($obj);
        if (empty($this->classes[$class])) {
            $data = $this->mapper->mapClass($class);;
            if ($data['is_gridfs']) {
                throw new \RuntimeException("@GridFS must be saved with file");
            }
            $collection = $data['name'];
            $this->classes[$class] = $this->db->selectCollection($collection);
        }

        $document = $this->mapper->validate($obj);
        $oldDoc   = $this->mapper->getRawDocument($obj, false);
        if ($oldDoc) {
            $update = $this->mapper->update($obj, $document, $oldDoc);

            if (empty($update)) {
                return $this;
            }

            if ($trigger_events) {
                $this->mapper->trigger('preUpdate', $obj, array(&$update, $this));
            }

            foreach ($update as $op => $value) {
                $this->classes[$class]->update(
                    array('_id' => $oldDoc['_id']), 
                    array($op => $value),
                    compact('w')
                );
            }

            $this->setObjectDocument($obj, $document);

            if ($trigger_events) {
                $this->mapper->trigger('postUpdate', $obj, array($update, $this,$oldDoc['_id']));
                $this->mapper->trigger('postSave', $obj, array($update, $this));
            }

            return $this;
        }

        if ($trigger_events) {
            $this->mapper->trigger('preCreate', $obj, array(&$document, $this));
        }
        if (empty($document['_id'])) {
            $document['_id'] = new MongoId;
        }

        $this->setObjectDocument($obj, $document);

        $ret = $this->classes[$class]->save($document, compact('w'));
        if ($trigger_events) {
            $this->mapper->trigger('postCreate', $obj, array($document, $this));
            $this->mapper->trigger('postSave', $obj, array($document, $this));
        }

        return $this;
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
