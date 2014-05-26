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
namespace ActiveMongo2\Generate;

use Notoj\Annotation;
use ActiveMongo2\Generate;

class Property extends Base
{
    protected $collection;
    protected $type = null;
    protected $rawName;
    protected $isId;

    protected function getTypeFromAnnotation($annotation)
    {
        foreach ($annotation->getOne('DataType') as $type) {
            if ($this->type === null) {
                $this->type = $type;
            } else {
                throw new \Exception("{$this->getPHPName()} has two data tyeps {$type} and {$this->type}");
            }
        }
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function __construct(Collection $col, Annotation $prop)
    {
        $this->collection = $col;
        $this->annotation = $prop;

        if ($prop->has('Id')) {
            $this->type = '_id';
        }
        foreach ($this->getCallback('Validate') as $val) {
            $this->getTypeFromAnnotation($val->annotation);
        }
        foreach ($this->getCallback('DefaultValue') as $val) {
            $this->getTypeFromAnnotation($val->annotation);
        }
        $this->isId = $this->annotation->has('Id');
    }

    public function isId()
    {
        return $this->isId;
    }

    public function getPHPName()
    {
        return $this->annotation['property'];
    }

    public function isPublic()
    {
        return in_array('public', $this->annotation['visibility']);
    }

    public function getProperty()
    {
        return $this->annotation['property'];
    }

    public function getCallback($filter)
    {
        $types = array();
        foreach ($this->collection->getAnnotationByName($filter) as $name => $type) {
            if ($this->annotation->has($name)) {
                $types[] = $type;
                $type->name = $name;
            }
        }
        return $types;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getPHPBaseVariable($prefix = '$doc')
    {
        if (!$this->isId() && $this->collection->is('GridFs')) {
            $prefix = $prefix . '["metadata"]';
        }
        return $prefix;
    }

    public function getPHPVariable($prefix = '$doc')
    {
        $prefix = $this->getPHPBaseVariable($prefix);
        return $prefix . "[" . var_export($this->getName(), true) . "]";
    }

    public function getReferenceCollection()
    {
        $ann = $this->annotation->getOne('Embed,EmbedOne,EmbedMany,ReferenceOne,Reference,ReferenceMany');
        if (empty($ann) || empty($ann['args'])) {
            return false;
        }

        return current($ann['args']);
    }

    public function getType()
    {
        return $this->type ?: 'String';
    }

    public function getRawName() 
    {
        if (!empty($this->rawName)) {
            return $this->rawName;
        }

        $field = $this->annotation->getOne('Field');
        if ($this->isId()) {
            return $this->rawName = '_id';
        } else if (!empty($field)) {
            return $this->rawName = current($field);
        }

        return $this->rawName = $this->getPHPName();
    }

    public function isCustom()
    {
        return $this->annotation['custom'] === true;
    }

    public function getName($prefix = false)
    {
        $property = $this->GetRawName();

        if ($prefix && !$this->isId() && $this->collection->is('GridFs')) {
            // It is an special case
            $property = "metadata.$property";
        }
        return $property;
    }

}
