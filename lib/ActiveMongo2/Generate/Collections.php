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

use Notoj\Dir as NDir;
use ArrayObject;

class Collections extends ArrayObject
{
    protected $files = array();
    protected $annotations;
    protected $validator;
    protected static $events = array(
        'preSave', 'postSave', 'preCreate', 'postCreate', 'onHydratation', 
        'preUpdate', 'postUpdate', 'preDelete', 'postDelete'
    );

    public function getFiles()
    {
        return $this->files;
    }

    public function getEvents()
    {
        return self::$events;
    }

    public function offsetExists($name)
    {
        return parent::offsetExists(strtolower($name));
    }

    public function offsetSet($name, $value)
    {
        return parent::offsetSet(strtolower($name), $value);
    }

    public function offsetGet($name)
    {
        return parent::offsetGet(strtolower($name));
    }

    public function getAnnotation()
    {
        return $this->annotations;
    }

    public function autoload()
    {
        $classes = array();
        foreach ($this as $key => $value) {
            $classes[strtolower($value->getClass())] = $value->getPath();
        } 
        return $classes;
    }

    public function byClass()
    {
        $cols = array();
        foreach ($this as $key => $value) {
            $name = $value->GetName();
            if ($name) {
                $cols[$value->getClass()] = $value->getArray();
            }
        } 
        return $cols;
    }

    public function byConnection()
    {
        $cols = array();
        foreach ($this as $key => $value) {
            $name = $value->GetClass();
            $cols[$name] = $value->getConnection();
        }

        return $cols;
    }

    public function byName()
    {
        $cols = array();
        foreach ($this as $key => $value) {
            $name = $value->GetName();
            if ($name) {
                $array = $value->getArray();
                if (empty($cols[$name]) || empty($array['parent'])) {
                    $cols[$name] = $array;
                }
            }
        } 
        return $cols;
    }

    public function map(\Closure $fnc)
    {
        foreach ($this as $key => $value) {
            $fnc($value, $key);
        }
        return $this;
    }

    protected function getIndexOrder($prop, $i = 0)
    {
        $args = $prop[$i]->getArgs();
        $order = strtolower(current((array)$args)) == 'desc' ? -1 : 1;
        if ($prop[1]->getAnnotation()->has('Geo')) {
            $order = '2dsphere';
        }
        if ($prop[1]->getAnnotation()->has('fulltext')) {
            $order = 'text';
        }

        return $order;
    }

    public function getIndexesFromCollections()
    {
        $indexes = array();
        foreach ($this as $col) {
            foreach ($col->getAnnotation()->get('Index,Unique,Fulltext', 'Property') as $prop) {
                $index = array(
                    'field' => array(),
                    'col' => $col,
                    'extra' => array()
                );
                $args = $prop->getArgs();
                $isFullText = $col->getAnnotation()->has('Fulltext');
                foreach ($args as $name => $order) {
                    if (is_numeric($name)) {
                        $name  = $order;
                        $order = 1;
                    }
                    $index['field'][$name] = is_numeric($order) ? $order+0 : $order;
                    if ($isFullText) {
                        $index['field'][$name] = 'text';
                    }
                }
                if ($col->getAnnotation()->has('Unique')) {
                    $index['extra'] = array("unique" => true);
                }

                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    public function getIndexes()
    {
        $indexes = $this->getIndexesFromCollections();

        foreach ($this->getAllPropertiesWithAnnotation('Unique,Index,Fulltext') as $prop) {
            $order = $this->getIndexOrder($prop);
            $index = array();
            $name  = $prop[1]->getParent()->getName() . "_" . $prop[1]->getName() . '_' .  $order;

            $index['prop']  = $prop[1];
            $index['field'] = array($prop[1]->getName() =>  $order);
            $index['extra'] = array('w' => 1);

            if ($prop[1]->getAnnotation()->has('Unique')) {
                $index['extra']  = array("unique" => true, 'w' => 1);
            }

            if (empty($indexes[$name])) {
                $indexes[$name] = $index;
            }
            
            $indexes[$name]['extra'] = array_merge($indexes[$name]['extra'], $index['extra']);
        }

        return $indexes;
    }

    public function getAllPropertiesWithAnnotation($ann, $with_arg = false)
    {
        $all = array();
        foreach ($this->getAllProperties() as $prop) {
            foreach ($prop->getAnnotation()->get($ann) as $a) {
                $args = $a->GetArgs();
                if (empty($with_arg) || !empty($args)) {
                    $all[] = [$a, $prop];
                }
            }
        }
        return $all;
    }
    
    public function getAllProperties()
    {
        $all = array();
        foreach ($this as $col) {
            foreach ($col->getProperties() as $prop) {
                $all[] = $prop;
            }
        }
        return $all;
    }

    public function getCollectionByName($name)
    {
        $names = array();
        foreach ($this as $col) {
            $names[] = $col->getName() ?: $col->getClass();
            if ($col->getName() == $name || $col->getClass() == strtolower($name)) {
                return $col;
            }
        }
        throw new \RuntimeException("Cannot find collection {$name} We have (" . implode(", ", $names) . ")");
    }

    public function getAllReferences()
    {
        static $refs;
        if (!empty($refs)) {
            return $refs;
        }

        $refCache = $this->getReferenceCache();
        $references = array(
            'Reference' => false,
            'ReferenceOne' => false,
            'ReferenceMany' => true,
        );

        $refs = array();
        foreach ($references as $type => $multi) {
            foreach ($this->getAllPropertiesWithAnnotation($type, true) as $ann) {
                list($ann, $prop) = $ann;
                $args = $ann->getArgs();
                $target = $this->getCollectionByName($args[0]);
                $args = array_merge(empty($args[1]) ? [] : $args[1], $refCache[$target->getClass()]);
                $refs[] = array(
                    'property'  => $prop,
                    'target'    => $target,
                    'update'    => $args,
                    'multi'     => $multi,
                    'deferred'  => $prop->getAnnotation()->has('Deferred'),
                );
            }
        }

        return $refs;
    }

    public function getReferenceCache()
    {
        static $refCache = [];
        if (!empty($refCache)) {
            return $refCache;
        }
        foreach ($this as $document) {
            $class = $document->getClass();
            $refCache[$class] = [];
            foreach ($document->getAnnotation()->get('RefCache', 'Class') as $args) {
                if (empty($args)) {
                    throw new \Exception("@RefCache expects at least one argument");
                }
                $refCache[$class] = array_merge($refCache[$class], $args->GetArgs());
            }
            $refCache[$class] = array_unique($refCache[$class]);
        }

        return $refCache;
    }

    public function getAnnotationByName($name)
    {
        static $cache = array();
        if (!empty($cache[$name])) {
            return $cache[$name];
        }
        $anns = array();
        foreach ($this->annotations->get($name, 'Callable') as $ann) {
            $type = new Type($ann, $name);
            $xname = current($ann->GetArgs());
            if (!empty($xname)) {
                $anns[$xname] = $type;
            }
        }
        return $cache[$name] = $anns;
    }

    protected function readCollections()
    {
        foreach ($this->annotations->getClasses('Persist,Embeddable') as $object) {
            if (empty($this[$object->GetName()])) {
                $this[$object->getName()] = new Collection($object, $this); 
                $this->files[] = $object->getFile();
            }
        }
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function getValidatorNS()
    {
        return $this->validator->getNamespace();
    }

    public function __construct(Array $dirs)
    {
        $dirs = array_merge(
            $dirs,
            array_map(function($dir) {
                return __DIR__ . '/../' . $dir;
            }, array('Filter', 'Plugin'))
        );
        $this->annotations = new NDir($dirs);
        $this->readCollections();

        $this->files = array_unique($this->files);

        $validator = new Validate('', '');
        $validator->setCollections($this);
        $this->validator = $validator->generateValidators();
    }
}
