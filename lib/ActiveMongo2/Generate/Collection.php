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
use Notoj\Annotation\AnnClass;
use ActiveMongo2\Generate;

class Collection extends Base
{
    protected $collections;

    public function getPlugins($type)
    {
        $plugins = array();
        foreach ($this->collections->getPlugins() as $name => $p) {
            if ($this->annotation->has($name)) {
                foreach ($p->getMethodsByAnnotation($type) as $method) {
                    $method->name = $name;
                    $plugins[] = $method;
                }
            } 
        }
        return $plugins;
    }

    public function __construct(AnnClass $annotation, Collections $collections)
    {
        $this->annotation  = $annotation;
        $this->collections = $collections;
        $parent     = $annotation->getParent();
        while ($parent) {
            if (empty($collections[$parent['class']])) {
                $collections[$parent['class']] = new self($parent, $collections);
            }
            $parent = $parent->getParent();
        }
    }

    public function getDefaults()
    {
        return $this->collections->getDefaults();
    }


    public function getTypes()
    {
        return $this->collections->getTypes();
    }

    public function getProperties()
    {
        $properties = array();
        foreach ($this->annotation->getProperties() as $prop) {
            $properties[] = (new Property($this, $prop))->setParent($this);
        }
        return $properties;
    }

    public function isGridFs()
    {
        return $this->annotation->has('GridFs');
    }

    public function __toString()
    {
        return $this->getClass();
    }

    public function getArray()
    {
        return [
            'class' => $this->getClass(),
            'file'  => $this->getPath(),
            'name'  => $this->getName(),
            'is_gridfs' => $this->isGridFs(),
            'parent' => $this->getParent() ? $this->GetParent()->getClass() : NULL,
            'disc'   => $this->isSingleCollection() ? $this->getDiscriminator() : NULL,
        ];
    }

    public function getDiscriminator($obj = false)
    {
        $args = $this->annotation->getOne('SingleCollection') ?: ['__type'];
        $prop = current($args);
        if ($obj) {
            // we expect it as an annotation object
            $ann = new Annotation;
            $ann->setMetadata([
                'type' => 'property',
                'property' => $prop,
            ]);
            $prop = new Property($this, $ann);
        }
        return $prop;
    }

    public function isSingleCollection($recursive = true)
    {
        return $this->annotation->has('SingleCollection') ||
            ($recursive && $this->getParent() && $this->getParent()->isSingleCollection());
    }

    public function getClass()
    {
        return strtolower($this->annotation['class']);
    }

    public function getHash()
    {
        return $this->getName() . '_' . sha1($this->getClass());
    }

    protected function getNameFromParent()
    {
        $parent = $this->getParent();
        while ($parent) {
            if ($parent->isSingleCollection(false)) {
                return $parent->getName();
            }
            $parent = $parent->getParent();
        }
        return NULL;
    }

    protected function getAnnotationArgs()
    {
        if (!$this->annotation->has('Persist') && !$this->annotation->has('Embeddable')) {
            return false;
        }
        return $this->annotation->getOne('Persist') ?: $this->annotation->getOne('Embeddable');
    }

    protected function getNameFromAnnotation($args, $ann)
    {
        foreach ($ann as $name) {
            if (!empty($args[$name])) {
                return $args[$name];
            }
        }
        return NULL;
    }

    public function getBackReferences()
    {
        $all  = $this->collections->getAllReferences();
        $name = $this->getName();

        return empty($all[$name]) ? [] : $all[$name];
    }

    public function getRefCache()
    {
        $cache = $this->collections->getReferenceCache();
        if (!empty($cache[$this->getClass()])) {
            return array_combine(
                $cache[$this->getClass()],
                $cache[$this->getClass()]
            );
        }
        return NULL;
    }

    public function getName()
    {
        $args = $this->getAnnotationArgs();
        if ($args === FALSE) {
            $name = NULL;
        } else if (($pname = $this->getNameFromParent())
            || ($pname = $this->getNameFromAnnotation($args, [0, 'collection']))) {
            $name = $pname;
        } else if ($this->isGridFs()) {
            $name = "fs";
        } else {
            $parts = explode("\\", $this->getClass());
            $name  = strtolower(end($parts)); 
        }

        return $name;
    }

    public function getAnnotation()
    {
        return $this->annotation;
    }
    
    public function getParent()
    {
        $parent = $this->annotation->getParent();
        if (empty($parent)) {
            return NULL;
        }
        return $this->collections[$parent['class']];
    }
}
