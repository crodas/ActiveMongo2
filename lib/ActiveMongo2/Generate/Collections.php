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

use Notoj\Annotations;
use Notoj\Dir as NDir;
use ArrayObject;

class Collections extends ArrayObject
{
    protected $files = array();
    protected $annotations;
    protected static $events = array(
        'preSave', 'postSave', 'preCreate', 'postCreate', 'onHydratation', 
        'preUpdate', 'postUpdate', 'preDelete', 'postDelete'
    );

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

    public function byName()
    {
        $cols = array();
        foreach ($this as $key => $value) {
            $name = $value->GetName();
            if ($name) {
                $cols[$name] = $value->getArray();
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

    public function getReferenceCache()
    {
        static $refCache = [];
        if (!empty($refCache)) {
            return $refCache;
        }
        foreach ($this as $document) {
            $class = $document->getClass();
            $refCache[$class] = [];
            foreach ($document->getAnnotation()->get('RefCache') as $args) {
                if (empty($args)) {
                    throw new \Exception("@RefCache expects at least one argument");
                }
                $refCache[$class] = array_merge($refCache[$class], $args['args']);
            }
            $refCache[$class] = array_unique($refCache[$class]);
        }

        return $refCache;
    }

    protected function getAnnotationByName($name)
    {
        $anns = array();
        foreach ($this->annotations->get($name) as $ann) {
            $type = new Type($ann, $name);
            foreach ($ann->get($name) as $arg) {
                $xname = current($arg['args'] ?: []);
                if (!empty($xname)) {
                    $anns[$xname] = $type;
                }
            }
        }
        return $anns;
    }

    public function getPlugins()
    {
        static $plugins = array();
        if (empty($plugins)) {
            $plugins = $this->getAnnotationByName('Plugin');
        }
        return $plugins;
    }

    public function getDefaults()
    {
        static $types = array();
        if (empty($types)) {
            $types = $this->getAnnotationByName('DefaultValue');
        }
        return $types;
    }

    public function getTypes()
    {
        static $types = array();
        if (empty($types)) {
            $types = $this->getAnnotationByName('Validate');
        }
        return $types;
    }

    protected function addDirs(Array $dirs)
    {
        foreach ($dirs as $dir) {
            $dir = new NDir($dir);
            $dir->getAnnotations($this->annotations);
            $this->files = array_merge($this->files, $dir->getFiles());
        }
    }

    protected function readCollections()
    {
        foreach (array('Persist', 'Embeddable') as $type) {
            foreach ($this->annotations->get($type) as $object) {
                $object = new Collection($object, $this);
                $this[$object->getClass()] = $object;
            }
        }

    }

    public function __construct(Array $dirs)
    {
        $this->annotations = new Annotations;
        $this->addDirs($dirs);
        $this->addDirs(array_map(function($dir) {
            return __DIR__ . '/../' . $dir;
        }, array('Filter', 'Plugin')));
        $this->readCollections();

        $this->files = array_unique($this->files);
    }
}