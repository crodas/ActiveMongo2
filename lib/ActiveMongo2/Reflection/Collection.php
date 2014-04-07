<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2014 ActiveMongo                                                  |
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
namespace ActiveMongo2\Reflection;

use ArrayObject;

class Collection extends ArrayObject
{
    protected $data;

    public function __construct(Array $data)
    {
        $this->data = $data;
        parent::__construct($data);
    }

    public function property($search)
    {
        return current($this->properties($search));
    }

    protected function propertiesByAnnotation($search)
    {
        $properties = array();
        $search     = substr($search, 1);
        foreach ($this->data['properties'] as $prop) {
            foreach ($prop['annotation'] as $ann) {
                if ($ann['method'] == $search) {
                    $properties[] = $prop;
                    break;  
                }
            }
        }

        return $properties;
    } 

    protected function propertiesFilter($search)
    {
        $properties = array();
        foreach ($this->data['properties'] as $prop) {
            if ($prop['property'] == $search) {
                $properties[] = $prop;
                break;
            }
        }

        return $properties;
    }

    public function properties($search)
    {
        $properties = array();
        if ($search[0] == '@') {
            $properties = $this->propertiesByAnnotation($search);
        } else if (!empty($this->data['properties'][$search])) {
            $properties[] = $this->data['properties'][$search];
        } else {
            $properties = $this->propertiesFilter($search);
        } 

        return $properties;
    }
}
