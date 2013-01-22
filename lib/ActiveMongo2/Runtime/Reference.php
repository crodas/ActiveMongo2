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

namespace ActiveMongo2\Runtime;

use ActiveMongo2\DocumentProxy;

class Reference implements DocumentProxy
{
    protected $class;
    protected $doc;
    protected $ref;
    protected $values;
    protected $map;

    protected static $all_objects = array();

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
            $id = sha1(serialize($this->ref));
            if (empty(self::$all_objects[$id])) {
                self::$all_objects[$id] = $this->class->findOne(array('_id' => $this->ref['$id']));
            }
            $this->doc = self::$all_objects[$id];
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
