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
    protected $docs = array();
    protected $uniq = null;

    public function __construct(Configuration $config, MongoClient $conn, $db)
    {
        $this->mapper = $config->initialize($this);
        $this->conn   = $conn;
        $this->db     = $conn->selectDB($db);
        $this->uniq   = "__status_" . uniqid(true);
    }
    
    public function command($command, $args = array())
    {
        return $this->db->command($command, $args);
    }

    public function getConnection()
    {
        return $this->conn;
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

        if (!empty($this->collections[$data['name']])) {
            return $this->collections[$data['name']];
        }

        $mongoCol = $this->db->selectCollection($data['name']);
        $this->collections[$data['name']] = new Collection($this, $data['class'], $mongoCol);
        return $this->collections[$data['name']];
    }

    public function registerDocument($class, Array $document)
    {
        $refl = new \ReflectionClass($class);
        if (PHP_MAJOR_VERSION >= 5 && PHP_MINOR_VERSION >= 5) {
            $doc = $refl->newInstanceWithoutConstructor();
        } else {
            $doc = $refl->newInstance();
        }
        $this->setObjectDocument($doc, $document);
        $this->mapper->trigger('onHydratation', $doc);

        return $doc;
    }

    protected function setObjectDocument($object, Array $document)
    {
        $this->mapper->populate($object, $document);
        $hash  = spl_object_hash($object);
        $prop  = $this->uniq;

        if (empty($object->$prop)) {
            $value = uniqid(true);
            $object->$prop = $value;
        } else {
            $value = $object->$prop;
        }

        $this->docs[$hash] = array($document, $value);
    }

    public function getRawDocument($object, $default = NULL)
    {
        $docid = spl_object_hash($object);
        if (empty($this->docs[$docid])) {
            if ($default === NULL) {
                throw new \RuntimeException("Cannot find document");
            } 
            return $default;
        }

        $doc  = $this->docs[$docid];
        $prop = $this->uniq;

        if (empty($object->$prop) || $object->$prop != $doc[1]) {
            if ($default === NULL) {
                throw new \RuntimeException("Cannot find document");
            } 
            return $default;
        }

        return $doc[0];
    }

    public function delete($obj, $safe = true)
    {
        $class = get_class($obj);
        if (empty($this->classes[$class])) {
            $collection = $this->mapper->mapClass(get_class($obj))['name'];
            $this->classes[$class] = $this->db->selectCollection($collection);
        }

        $document = $this->mapper->getDocument($obj);
        $hash = spl_object_hash($obj);

        unset($this->docs[$hash]);

        if (empty($document['_id'])) {
            throw new \RuntimeException("Cannot delete without an id");
        }

        $this->classes[$class]->remove(array('_id' => $document['_id']));
    }

    protected function array_diff_ex($arr1, $arr2)
    {
        $diff = array();
        foreach ($arr1 as $key => $value) {
            if ($arr2[$key] !== $arr1[$key]) {
                $diff[$key] = $value;
            }
        }
        return $diff;
    }

    public function is($collection, $object)
    {
        $class = $this->mapper->mapCollection($collection)['class'];
        if ($object instanceof Reference) {
            return $class == $object->getClass();
        }
        return $object instanceof $class;
    }

    public function save($obj, $safe = true)
    {
        if ($obj instanceof DocumentProxy) {
            $obj = $obj->getObject();
            if (empty($obj)) {
                return $this;
            }
        }
        $class = get_class($obj);
        if (empty($this->classes[$class])) {
            $collection = $this->mapper->mapClass(get_class($obj))['name'];
            $this->classes[$class] = $this->db->selectCollection($collection);
        }

        $document = $this->mapper->validate($obj);
        $oldDoc   = $this->getRawDocument($obj, false);
        if ($oldDoc) {
            $update = $this->mapper->update($obj, $document, $oldDoc);

            if (empty($update)) {
                return $this;
            }

            $this->mapper->trigger('preUpdate', $obj, array(&$update, $this));

            foreach ($update as $op => $value) {
                $this->classes[$class]->update(
                    array('_id' => $oldDoc['_id']), 
                    array($op => $value),
                    array('safe' => $safe)
                );
            }

            $this->mapper->trigger('postUpdate', $obj, array($this, $update, $oldDoc['_id']));

            $this->setObjectDocument($obj, $document);

            return $this;
        }

        $this->mapper->trigger('preCreate', $obj, array(&$document, $this));
        if (empty($document['_id'])) {
            $document['_id'] = new MongoId;
        }

        $this->setObjectDocument($obj, $document);

        $ret = $this->classes[$class]->save($document, array('w' => 1));
        $this->mapper->trigger('postCreate', $obj, array($document));

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
