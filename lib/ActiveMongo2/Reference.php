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

use ActiveMongo2\Filter as f;

class Reference implements DocumentProxy, \JsonSerializable
{
    protected $class;
    protected $_class;
    protected $doc;
    protected $ref;
    protected $map;
    protected $conn;
    protected $mapper;

    public function __construct(Array $info, $class, $conn, Array $map, $mapper)
    {
        $this->ref    = $info;
        $this->map    = $map;
        $this->_class = $class;
        $this->class  = $conn->getCollection($class);
        $this->conn   = $conn;
        $this->mapper = $mapper;
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
        return $this->ref;
    }

    public function getObjectOrReference()
    {
        return $this->doc ?: $this->ref;
    }

    public function jsonSerialize() 
    {
        return $this->getObject();
    }

    private function _loadDocument()
    {
        if (!$this->doc) {
            try {
                $this->doc = $this->class->getById($this->ref['$id']);
            } catch (\Exception $e) {
                if ($this->conn->GetConfiguration()->failOnMissingReference()) {
                    // throw exception
                    throw $e;
                }
                // do nothing
            }
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
        if (array_key_exists($name, $this->ref)) {
            $this->ref[$name] = $value;
        }
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->map)) {
            $expected = $this->map[$name];
            if (array_key_exists($expected, $this->ref)) {
                $doc = $this->ref[$expected];
                if (is_array($doc)) {
                    $tmp = $doc;
                    f\_hydratate_reference_one($tmp, [], $this->conn, $this->mapper);
                    return $tmp;
                }
                return $doc;
            }

            if ($expected == '_id') {
                // avoid one query!
                return $this->ref['$id'];
            }
        }

        $this->_loadDocument();
        return $this->doc->$name;
    }
}
