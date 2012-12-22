<?php
namespace ActiveMongo2;

use MongoClient;
use ActiveMongo2\Runtime\Utils;
use MongoId;

class Connection
{
    protected $conn;

    /** 
     *  Collections to Classes mapping
     */
    protected $collections;

    /**
     *  Classes to Collections mapping
     */
    protected $classes;
    protected $mapping = array();
    protected $docs = array();
    protected $uniq = null;

    public function __construct(MongoClient $conn, $db)
    {
        $this->conn = $conn;
        $this->db   = $conn->selectDB($db);
        $this->uniq = "__status_" . uniqid(true);
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function registerNamespace($regex)
    {
        if (strpos($regex, '{{collection}}') === false) {
            throw new \RuntimeException("The namespace must have a {{collection}} placeholder");
        }
        $this->mapping[] = $regex;
        return $this;
    }

    public function getDocumentClass($collection)
    {
        foreach ($this->mapping as $map) {
            $class = str_replace('{{collection}}', $collection, $map);
            if (Utils::class_exists($class)) {
                return $class;
            }
        }
        return null;
    }

    public function getCollection($collection)
    {
        if (!empty($this->collections[$collection])) {
            return $this->collections[$collection];
        }

        $class = $this->getDocumentClass($collection);
        if ($class) {
            $mongoCol = $this->db->selectCollection(Runtime\Serialize::getCollection($class));
            $this->collections[$collection] = new Collection($this, $class, $mongoCol);
            return $this->collections[$collection];
        } else if (Utils::class_exists($collection)) {
            $mongoCol = $this->db->selectCollection(Runtime\Serialize::getCollection($collection));
            $this->collections[$collection] = new Collection($this, $collection, $mongoCol);
            return $this->collections[$collection];
        }


        throw new \RuntimeException("Cannot find mapping object for {$collection}");
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
        Runtime\Events::run('onHydratation', $doc);
        return $doc;
    }

    protected function setObjectDocument($object, Array $document)
    {
        Runtime\Serialize::setDocument($object, $document, $this);
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
            $collection =  Runtime\Serialize::getCollection($obj);
            $this->classes[$class] = $this->db->selectCollection($collection);
        }

        $document = Runtime\Serialize::getDocument($obj, $this);
        $hash = spl_object_hash($obj);

        unset($this->docs[$hash]);

        if (empty($document['_id'])) {
            throw new \RuntimeException("Cannot delete without an id");
        }

        $this->classes[$class]->remove(array('_id' => $document['_id']));
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
            $collection =  Runtime\Serialize::getCollection($obj);
            $this->classes[$class] = $this->db->selectCollection($collection);
        }

        $document = Runtime\Serialize::getDocument($obj, $this);
        $oldDoc   = $this->getRawDocument($obj, false);
        if ($oldDoc) {
            $changes  = array_diff($document, $oldDoc);
            if (empty($changes)) {
                // nothing to do!
                return $this;
            }

            $update = Runtime\Serialize::changes($obj, $changes, $oldDoc);

            Runtime\Events::run('preUpdate', $obj, array(&$update, $this));

            $this->classes[$class]->update(
                array('_id' => $oldDoc['_id']), 
                $update,
                array('safe' => $safe)
            );
            Runtime\Events::run('postUpdate', $obj);
            $this->setObjectDocument($obj, $document);

            return $this;
        }

        Runtime\Events::run('preCreate', $obj, array(&$document, $this));
        if (empty($document['_id'])) {
            $document['_id'] = new MongoId;
        }

        $this->classes[$class]->save($document, array('safe' => $safe));
        Runtime\Events::run('postCreate', $obj);

        $this->setObjectDocument($obj, $document);

        return $this;
    }
}
