<?php
namespace ActiveMongo2;

use MongoCollection;
use ActiveMongo2\Runtime\Utils;

class Collection
{
    protected $conn;
    protected $class;
    protected $col;

    public function __construct(Connection $conn, $class, MongoCollection $col)
    {
        if (!Utils::class_exists($class)) {
            throw new \RuntimeException("Cannot find {$class} class");
        }
        $this->conn  = $conn;
        $this->col   = $col;
        $this->class = $class;
    }

    public function findAndModify($query, $update, $options)
    {
        $response = $this->col->findAndModify($query, $update, null, $options);

        return $this->conn->registerDocument($this->class, $response);
    }

    public function find($query = array(), $fields = array())
    {
        return new Cursor($query, $fields, $this->conn, $this->col, $this->class);
    }

    public function findOne($query = array(), $fields = array())
    {
        $doc =  $this->col->findOne($query, $fields);

        return $this->conn->registerDocument($this->class, $doc);
    }
}
