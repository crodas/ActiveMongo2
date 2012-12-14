<?php

namespace ActiveMongo2\Runtime;

class Reference
{
    protected $class;
    protected $doc;
    protected $ref;
    protected $values;
    protected $map;

    public function __construct(Array $info, $class, $conn, $map)
    {
        $this->ref    = $info;
        $this->class  = $conn->getCollection($class);
        $this->values = !empty($info['_extra']) ? $info['_extra'] : array();
        $this->map    = $map;

        $this->values['_id'] = $info['$id'];
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
        if (!empty($this->map[$name])) {
            $zname = $this->map[$name];
            if (array_key_exists($zname, $this->values)) {
                return $this->values[$zname];
            }
        }

        $this->_loadDocument();
        return $this->doc->$name;
    }
}
