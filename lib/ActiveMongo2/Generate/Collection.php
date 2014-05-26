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
namespace ActiveMongo2\Generate;

use Notoj\Notoj;
use Notoj\Annotation;
use Notoj\Annotation\AnnClass;
use ActiveMongo2\Generate;

class Collection extends Base
{
    protected $collections;
    protected $validator;
    protected $properties = array();
    protected $_name;

    public function getPlugins($type)
    {
        $plugins = array();
        foreach ($this->collections->getAnnotationByName('Plugin') as $name => $p) {
            if ($this->annotation->has($name)) {
                foreach ($p->getMethodsByAnnotation($type) as $method) {
                    $method->name = $name;
                    $plugins[] = $method;
                }
            } 
        }
        return $plugins;
    }

    public function onMapping()
    {
        foreach ($this->getPlugins('onMapping') as $plugin) {
            $ann = $plugin->getAnnotation();
            if ($plugin->isMethod()) {
                $class  = $plugin->getClass();
                $method = $plugin->getMethod();
                if (!class_exists($class)) {
                    require $ann['file'];
                }
                if ($plugin->isStatic())  {
                    $class::$method($this);
                } else {
                    $obj = new $class;
                    $class->$method($this);
                }
            }
        }
    }

    public function __construct(AnnClass $annotation, Collections $collections)
    {
        $this->annotation  = $annotation;
        $this->collections = $collections;

        $parent = $annotation->getParent();
        while ($parent) {
            if (empty($collections[$parent['class']])) {
                $collections[$parent['class']] = new self($parent, $collections);
            }
            $parent = $parent->getParent();
        }

        foreach ($this->annotation->getProperties() as $prop) {
            $this->properties[] = (new Property($this, $prop))->setParent($this);
        }

        $this->onMapping();
    }

    public function getTypes()
    {
        return $this->collections->getTypes();
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function defineProperty($annotation, $name)
    {
        $ann = Notoj::parseDocComment($annotation);
        $ann->setMetadata(array(
            'visibility'    => ['public'],
            'property'      => $name,
            'custom'        => true,
        ));
        $this->properties[] = (new Property($this, $ann))->setParent($this);
    }

    public function __toString()
    {
        return $this->getClass();
    }

    public function getArray()
    {
        return [
            'class' => $this->getClass(),
            'dir'   => dirname($this->getPath()),
            'file'  => $this->getPath(),
            'name'  => $this->getName(),
            'zname' => $this->getSafeName(),
            'is_gridfs' => $this->is('GridFs'),
            'parent' => $this->getParent() ? $this->GetParent()->getClass() : NULL,
            'disc'   => $this->is('SingleCollection') ? $this->getDiscriminator() : NULL,
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

    public function getClass()
    {
        return strtolower($this->annotation['class']);
    }

    public function getSafeName()
    {
        return preg_replace('/[^a-z0-9]/i', '___', $this->getName());
    }

    public function getHash()
    {
        return $this->getSafeName() . '_' . sha1($this->getClass());
    }

    protected function getNameFromParent()
    {
        $parent = $this->getParent();
        while ($parent) {
            if ($parent->is('SingleCollection', false)) {
                return $parent->getName();
            }
            $parent = $parent->getParent();
        }
        return NULL;
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

    public function getForwardReferences()
    {
        $self = $this;
        return array_filter($this->collections->getAllReferences(), function($obj) use ($self) {
            return $obj['target'] == $self;
        });
    }

    public function getBackReferences()
    {
        $self = $this;
        return array_filter($this->collections->getAllReferences(), function($obj) use ($self) {
            return $obj['property']->getParent() == $self;
        });
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
        if (!empty($this->_name)) {
            return $this->_name;
        }
        $args = $this->getAnnotationArgs();
        if ($args === FALSE) {
            $name = NULL;
        } else if (($pname = $this->getNameFromParent())
            || ($pname = $this->getNameFromAnnotation($args, [0, 'collection']))) {
            $name = $pname;
        } else if ($this->is('GridFs')) {
            $name = "fs";
        } else {
            $parts = explode("\\", $this->getClass());
            $name  = strtolower(end($parts)); 
        }

        return $this->_name = $name;
    }

    public function getAnnotationByName($name)
    {
        return $this->collections->getAnnotationByName($name);
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
