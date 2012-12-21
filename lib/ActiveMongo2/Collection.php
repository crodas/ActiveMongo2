<?php
namespace ActiveMongo2;

use MongoCollection;
use ActiveMongo2\Runtime\Utils;

class Collection 
{
    protected $zconn;
    protected $class;
    protected $zcol;

    public function __construct(Connection $conn, $class, MongoCollection $col)
    {
        if (!Utils::class_exists($class)) {
            throw new \RuntimeException("Cannot find {$class} class");
        }
        $this->zconn  = $conn;
        $this->zcol   = $col;
        $this->class = $class;
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
        /** 
        var_dump(array(
            'collection' => (string)$this->zcol,
            'query' => $query
        ));
        **/
        return new Cursor($query, $fields, $this->zconn, $this->zcol, $this->class);
    }

    public function findOne($query = array(), $fields = array())
    {
        /**
        var_dump(array(
            'collection' => (string)$this->zcol,
            'query' => $query
        ));
        **/
        $doc =  $this->zcol->findOne($query, $fields);
        if (empty($doc)) {
            return $doc;
        }

        return $this->zconn->registerDocument($this->class, $doc);
    }
}
