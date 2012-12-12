<?php
namespace ActiveMongo2;

use MongoCollection;
use MongoCursor;

class Cursor extends MongoCursor
{
    protected $class;
    protected $conn;

    public function __construct(Array $query, Array $fields, Connection $conn, MongoCollection $col, $class)
    {
        $this->conn  = $conn;
        $this->class = $class;
        parent::__construct($conn->getConnection(), (string)$col, $query, $fields);
    }

    public function current()
    {
        $current = parent::current();
        return $this->conn->registerDocument($this->class, $current);
    }
}
