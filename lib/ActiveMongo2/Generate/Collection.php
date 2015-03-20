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
use Notoj\Annotation\Annotation;
use Notoj\Object\zClass;
use ActiveMongo2\Generate;

class Collection extends Base
{
    protected $collections;
    protected $validator;
    protected $properties = array();
    protected $_name;

    protected function addWeight($p, $method) {
        if ($p->has('Last')) {
            $method->pos = -1 * $method->pos * 100;
        } else if ($p->has('First')) {
            $method->pos = $method->pos * 100;
        }
    }

    public function serializeAnnArgs(Annotation $ann)
    {
        $args = [];
        foreach ($ann->getArgs() as $arg) {
            if ($arg instanceof Annotation) {
                $arg = $this->serializeAnnArgs($arg);
            }
            $args[] = $arg;
        }

        return $args;
    }

    public function getConnection()
    {
        if ($this->annotation->has('Connection')) {
            $conn = current($this->annotation->getOne('Connection')->getArgs());
            if (!empty($conn)) {
                return $conn;
            }
        }
        return 'default';
    }

    protected function getMyPlugins($type)
    {
        $plugins = array();
        foreach ($this->collections->getAnnotation()->getClasses('Plugin') as $class) {
            foreach ($class->get('Plugin') as $annotation) {
                $name = current($annotation->getArgs());
                if ($name && $this->annotation->has($name)) {
                    $plugins[] = [$name, $class];
                    break;
                }
            }
        }

        return $plugins;
    }

    public function getPlugins($type)
    {
        $plugins = array();
        $index   = 0;

        foreach ($this->getMyPlugins($type) as $plugin) {
            list($name, $class) = $plugin;
            foreach ($class->getMethods($type) as $method) {
                $method = new Type($method->getOne($type), $name);
                $method->pos = ++$index;
                $this->addWeight($class, $method);
                $plugins[] = $method;
            }
        }

        usort($plugins, function($a, $b) {
            return $b->pos - $a->pos;
        });

        return $plugins;
    }

    public function onMapping()
    {
        foreach ($this->getPlugins('onMapping') as $plugin) {
            $plugin->getAnnotation()->getObject()->exec($this);
        }
    }

    protected function parseProperties()
    {
        foreach ($this->annotation->getProperties() as $prop) {
            if ($prop->getAnnotations()->count() > 0) {
                $this->properties[] = (new Property($this, $prop))->setParent($this);
            }
        }

        $this->onMapping();
    }

    protected function processParentClasses()
    {
        $parent = $this->annotation->getParent();
        while ($parent) {
            if (empty($this->collections[$parent->getName()])) {
                $this->collections[$parent->getName()] = new self($parent, $this->collections);
            }
            $parent = $parent->getParent();
        }

    }

    public function __construct(zClass $annotation, Collections $collections)
    {
        $this->annotation  = $annotation;
        $this->collections = $collections;
        $this->processParentClasses();
        $this->parseProperties();
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
        $ann  = Notoj::parseDocComment($annotation);
        $name = $name[0] == '$' ? $name : '$' . $name; 
        $prop = new \crodas\ClassInfo\Definition\TProperty($name);
        $prop = \Notoj\Object\Base::create($prop, NULL);
        $this->properties[] = (new Property($this, $prop, true))->setParent($this);
    }

    public function __toString()
    {
        return strtolower($this->annotation->getName());
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
        $prop = '__type';
        if ($single = $this->annotation->getOne('SingleCollection')) {
            $args = $single->getArgs();
            if (!empty($args)) {
                $prop = current($args);
            }
        }
        if ($obj) {
            $property = new \crodas\ClassInfo\Definition\TProperty($prop);
            $prop = new Property($this, \Notoj\Object\Base::create($property, NULL));
        }
        return $prop;
    }

    public function getClass()
    {
        return strtolower($this->annotation->getName());
    }

    public function getSafeName()
    {
        return preg_replace('/[^a-z0-9]/i', '___', $this->getName());
    }

    public function getHash()
    {
        return $this->getSafeName() . '_' . sha1($this->annotation->getName());
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

    protected function getNameFromAnnotation($annotation, $ann)
    {
        $args = $annotation->GetArgs();
        foreach ($ann as $name) {
            if (!empty($args[$name])) {
                return $args[$name];
            }
        }
        
        if ($this->is('GridFs')) {
            return "fs";
        }

        $parts = explode("\\", $this->annotation->getName());
        $name  = strtolower(end($parts)); 
        return $name;
    }

    public function getForwardReferences()
    {
        $self = $this;
        return array_filter($this->collections->getAllReferences(), function($obj) use ($self) {
            return $obj['target'] === $self;
        });
    }

    public function getBackReferences()
    {
        $self = $this;
        return array_filter($this->collections->getAllReferences(), function($obj) use ($self) {
            return $obj['property']->getParent() === $self;
        });
    }

    public function getRefCache()
    {
        $cache = $this->collections->getReferenceCache();
        $class = strtolower($this->annotation->getName());
        if (!empty($cache[$class])) {
            return array_combine(
                $cache[$class],
                $cache[$class]
            );
        }
        return NULL;
    }

    public function getName()
    {
        if ($this->_name) {
            return $this->_name;
        }
        $args = $this->getAnnotationArgs();
        if ($args === FALSE) {
            return NULL;
        } 
        
        return $this->_name =  $this->getNameFromParent()
            ?: $this->getNameFromAnnotation($args, [0, 'collection']);
    }

    public function getAnnotationByName($name)
    {
        return $this->collections->getAnnotationByName($name);
    }

    public function getCollections()
    {
        return $this->collections;
    }

    public function getParent()
    {
        $parent = $this->annotation->getParent();
        if (empty($parent)) {
            return NULL;
        }
        return $this->collections[$parent->getName()];
    }
}
