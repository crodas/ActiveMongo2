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
        return new Cursor($query, $fields, $this->zconn, $this->zcol, $this->class);
    }

    public function findOne($query = array(), $fields = array())
    {
        $doc =  $this->zcol->findOne($query, $fields);

        return $this->zconn->registerDocument($this->class, $doc);
    }
}
