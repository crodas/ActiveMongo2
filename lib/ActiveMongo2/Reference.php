<?php

namespace ActiveMongo2;

class Reference
{
    protected $class;
    protected $doc;
    protected $ref;

    public function __construct(Array $info, $class, $conn)
    {
        $this->ref   = $info;
        $this->class = $conn->getCollection($class);
    }

    public function getObject()
    {
        return $this->doc;
    }

    public function getReference()
    {
        return $this->ref;
    }

    private function _loadDocument()
    {
        if (!$this->doc) {
            $this->doc = $this->class->findOne(array('_id' => $this->ref['$id']));
        }
    }

    public function __call($name, $args)
    {
        $this->_loadDocument();
        return call_user_func_array(array($this->doc, $name), $args);
    }

    public function __set($name, $value)
    {
        $this->_loadDocument();
        $this->doc->{$name} = $value;
    }

    public function __get($name)
    {
        $this->_loadDocument();
        return $this->doc->$name;
    }
}
