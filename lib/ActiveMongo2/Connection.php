<?php
namespace ActiveMongo2;

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

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function registerNamespace($regex)
    {
        if (strpos($regex, '{{collection}}') === false) {
            throw new \RuntimeException("The namespace must have a {{collection}} placeholder");
        }
        $this->mapping[] = $regex;
        return $this;
    }

    public function getCollection($collection)
    {
        if (!empty($this->collections[$collection])) {
            return $this->collections[$collection];
        }


        if (class_exists($collection)) {
            $mongoCol = $this->conn->selectCollection(Runtime\Serialize::getCollection($collection));
            $this->collections[$collection] = new Collection($this, $collection, $mongoCol);
            return $this->collections[$collection];
        } else {
            foreach ($this->mapping as $map) {
                $class = str_replace('{{collection}}', $collection, $map);
                if (class_exists($class)) {
                    $mongoCol = $this->conn->selectCollection(Runtime\Serialize::getCollection($class));
                    $this->collections[$collection] = new Collection($this, $class, $mongoCol);
                    return $this->collections[$collection];
                }
            }
        }


        throw new \RuntimeException("Cannot find mapping object for {$collection}");
    }

    public function registerDocument($class, Array $document)
    {
        $doc = new $class;
        $this->setObjectDocument($doc, $document);
        return $doc;
    }

    protected function setObjectDocument($object, Array $document)
    {
        Runtime\Serialize::setDocument($object, $document);
        $this->docs[spl_object_hash($object)] = $document;
    }

    public function save($obj)
    {
        $class = get_class($obj);
        if (empty($this->classes[$class])) {
            $collection =  Runtime\Serialize::getCollection($obj);
            $this->classes[$class] = $this->conn->selectCollection($collection);
        }

        $document = Runtime\Serialize::getDocument($obj);
        $hash = spl_object_hash($obj);
        if (!empty($this->docs[spl_object_hash($obj)])) {
            $oldDoc  = $this->docs[$hash];
            $updated = array_diff($document, $oldDoc);
            if (empty($updated)) {
                // nothing to do!
                return $this;
            }
            
            Runtime\Events::run('preUpdate', $obj, array(&$document, $this));
            Runtime\Events::run('postUpdate', $obj);
            return $this;
        }

        Runtime\Events::run('preCreate', $obj, array(&$document, $this));
        $this->classes[$class]->save($document);
        Runtime\Events::run('postCreate', $obj);

        $this->setObjectDocument($obj, $document);

        return $this;
    }
}
