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

class Reference implements DocumentProxy
{
    protected $class;
    protected $_class;
    protected $doc;
    protected $ref;

    protected static $all_objects = array();

    public function __construct(Array $info, $class, $conn)
    {
        $this->ref    = $info;
        $this->_class = $class;
        $this->class  = $conn->getCollection($class);
    }

    public function getObject()
    {
        $this->_loadDocument();
        return $this->doc;
    }

    public function getClass()
    {
        return $this->_class;
    }

    public function getReference()
    {
        return $this->doc ?: $this->ref;
    }

    private function _loadDocument()
    {
        if (!$this->doc) {
            $id = $this->ref['$ref'] . ':' . $this->ref['$id'];
            if (empty(self::$all_objects[$id])) {
                self::$all_objects[$id] = $this->class->findOne(array('_id' => $this->ref['$id']));
            }
            $this->doc = self::$all_objects[$id];
        }
    }

    public function __call($name, $args)
    {
        if (!empty($this->ref[$name])) {
            return $this->ref[$name];
        }

        $this->_loadDocument();

        if (!empty($this->doc->$name)) {
            return $this->doc->$name;
        }

        return call_user_func_array(array($this->doc, $name), $args);
    }

    public function __set($name, $value)
    {
        $this->_loadDocument();
        $this->doc->{$name} = $value;
    }

    public function __get($name)
    {
        if (!empty($this->ref[$name])) {
            return $this->ref[$name];
        }

        $this->_loadDocument();
        return $this->doc->$name;
    }
}
