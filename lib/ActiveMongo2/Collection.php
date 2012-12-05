<?php
namespace ActiveMongo2;

use MongoCollection;

class Collection
{
    protected $conn;
    protected $class;
    protected $col;

    public function __construct(Connection $conn, $class, MongoCollection $col)
    {
        if (!class_exists($class)) {
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
}
