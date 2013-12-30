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

class Property
{
    protected $collection;
    protected $annotation;

    public function __construct(Collection $col, Annotation $prop)
    {
        $this->collection = $col;
        $this->annotation = $prop;
    }

    public function isId()
    {
        return $this->annotation->has('Id');
    }

    public function getPHPName()
    {
        return $this->annotation['property'];
    }

    public function isPublic()
    {
        return in_array('public', $this->annotation['visibility']);
    }

    public function getDefault() 
    {
        $defaults = array();
        foreach ($this->collection->getDefaults() as $name => $type) {
            if ($this->annotation->has($name)) {
                $defaults[] = $type;
            }
        }
        return $defaults;
    }

    public function getType()
    {
        $types = array();
        foreach ($this->collection->getTypes() as $name => $type) {
            if ($this->annotation->has($name)) {
                $types[] = $type;
            }
        }
        return $types;
    }

    public function getPHPVariable()
    {
        $prefix = '$doc';
        if (!$this->isId() && $this->collection->isGridFS()) {
            $prefix = '$doc["metadata"]';
        }
        return $prefix . "[" . var_export($this->getName(), true) . "]";
    }

    public function getName($prefix = false)
    {
        if ($this->isId()) {
            return '_id';
        }

        $property = $this->getPHPName();

        if ($prefix && $this->collection->isGridFs()) {
            // It is an special case
            $property = "metadata.$property";
        }
        return $property;
    }

}
