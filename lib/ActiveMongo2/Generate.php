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

use Notoj;
use WatchFiles\Watch;
use crodas\SimpleView\FixCode;
use crodas\File;
use crodas\Path;

class Generate
{
    protected $files = array();
    protected $config;

    public function getParentClasses($annotations)
    {
        $parents = [];
        foreach ($annotations->get('Persist') as $object) {
            if (!$object->isClass()) continue;
            $class = strtolower($object['class']);
            $parents[$class]  = $object;
        }
        return $parents;
    }

    protected function loadAnnotations()
    {
        $annotations  = new Notoj\Annotations;
        foreach ($this->config->getModelPath() as $path) {
            $dir = new Notoj\Dir($path);
            $dir->getAnnotations($annotations);
            $this->files = array_merge($this->files, $dir->getFiles());
        }

        foreach (array('Filter', 'Plugin') as $d) {
            $dir = new Notoj\Dir(__DIR__ . "/$d");
            $dir->getAnnotations($annotations);
        }

        return $annotations;
    }

    protected function checkingGridFsStream($object)
    {
        if (!$object->has('GridFs')) {
            foreach ($object->getProperties() as $prop) {
                if ($prop->has('Stream')) {
                    throw new \RuntimeException('@Stream nly works with @GridFS');
                }
            }
        }
    }

    protected function getDocumentClasses($annotations)
    {
        $return = [];

        foreach (array('Persist', 'Embeddable') as $type) {
            foreach ($annotations->get($type) as $object) {
                if ($object->isClass()) {
                    $this->checkingGridFsStream($object);
                    $return[] = [$type, $object];
                }
            }
        }

        return $return;
    }

    protected function getCallback($annotation)
    {
        if ($annotation->isMethod()) {
            $function = "\\" . $annotation['class'] . "::" . $annotation['function'];
        } else if ($annotation->isFunction()) {
            $function = "\\" . $annotation['function'];
        }

        return $function;
    }

    protected function generateUniqueIndex($annotations, &$indexes, $class_mapper)
    {
        foreach ($annotations->get('Unique') as $prop) {
            if (!$prop->isProperty()) continue;
            if (empty($class_mapper[strtolower($prop['class'])])) {
                continue;
            }
            $collection = $class_mapper[strtolower($prop['class'])]['name'];
            foreach ($prop->get('Unique') as $anno) {
                $indexes[] = array($collection,  array($prop['property'] => 1), array('unique' => 1));
            }
        }
    }


    protected function generateHooks($types, $annotations)
    {
        $files = array();
        foreach ($types as $operation => $var) {
            $$var = array();
            foreach ($annotations->get($operation) as $validator) {
                foreach ($validator->get($operation) as $val) {
                    $type = current($val['args']);
                    if (!empty($type)) {
                        $return[$var][$type] = $this->GetCallback($validator);
                        $files[$type] = $this->getRelativePath($validator['file']);
                    }
                }
            }
        }
        $return['files'] = $files;

        return $return;
    }

    public function __construct(Configuration $config, Watch $watcher)
    {
        $this->files  = array();
        $this->config = $config;
        $annotations  = $this->loadAnnotations();

        $collections = new Generate\Collections((array)$config->getModelPath(), $this);
        $fixPath = function($value) {
            $value->setPath($this->getRelativePath($value->getPath()));
        };
        $collections->map($fixPath);
        array_map($fixPath, $collections->getDefaults());
        array_map($fixPath, $collections->getTypes());
        array_map($fixPath, $collections->getPlugins());

        $parents  = $this->getParentClasses($annotations); 
        $refCache = $collections->getReferenceCache($annotations); 

        foreach ($this->getDocumentClasses($annotations) as $docClass) {
            list($type, $object) = $docClass;
            $parent = $object->getParent();

            $data = array(
                'class' => strtolower($object['class']), 
                'file'  => $this->getRelativePath($object['file']),
                'annotation' => $object,
                'is_gridfs'  => $object->has('GridFs'),
                'parent'     => $parent ? strtolower($parent['class']) : NULL,
            );

            if ($object->has('SingleCollection')) {
                $data['disc'] = $object->getOne('SingleCollection') ?: ['__type'];
                $data['disc'] = current($data['disc']);
            }

            foreach ($object->get($type) as $ann) {
                $data['name'] = $ann['args'] ? current($ann['args']) : null;
                while ($parent) {
                    $class = strtolower($parent['class']);
                    if (!empty($parents[$class])) {
                        if ($parents[$class]->has('SingleCollection') || empty($data['name'])) {
                            $data['name'] = current($parents[$class]->getOne('Persist'));
                            $data['disc'] = $parents[$class]->getOne('SingleCollection') ?: ['__type'];
                            $data['disc'] = current($data['disc']);
                        }
                    } else {
                        // Exception to the rule, the parent class
                        // doesn't have any @Persist
                        $docs[$parent['class']] = array(
                            'class' => strtolower($parent['class']),
                            'is_gridfs' => false,
                            'name'      => sha1($parent['class']),
                            'annotation' => $parent,
                            'file'       => $this->getRelativePath($parent['file']),
                            'parent'    => ($p = $parent->getParent()) ? strtolower($p['class']) : NULL,
                        );
                    }
                    $parent = $parent->GetParent();
                } 

                if (empty($data['name'])) {
                    if ($data['is_gridfs']) {
                        $data['name'] = 'fs';
                    } else {
                        $data['name'] = explode("\\", $data['class']);
                        $data['name'] = strtolower(end($data['name']));
                    }
                }

                if (!empty($data['disc'])) {
                    // give them some weird name
                    if (empty($docs[$data['name']])) {
                        $docs[$data['name']] = $data;
                    } else {
                        $docs[$data['class']] = $data;
                    }
                } else {
                    $docs[$data['name']] = $data;
                }
            }
        }

        $hookTypes  = [
            'Validate' => 'validators', 'Hydratate' => 'hydratations',
            'DefaultValue' => 'defaults',
        ];
        $hooks = $this->generateHooks($hookTypes, $annotations);

        $target       = $config->getLoader();
        $namespace    = sha1($target);
        $class_mapper = $this->getClassMapper($docs);

        $indexes = array();
        $this->generateUniqueIndex($annotations, $indexes, $class_mapper);

        $self = $this;
        $code = Template\Templates::get('documents')
            ->render(array_merge(compact(
                'docs', 'namespace',
                'mapper', 'indexes', 'self',
                'refCache',  'collections'
            ), $hooks), true);

        File::write($target, FixCode::fix($code));
        $this->files = array_unique($this->files);

        foreach ($this->files as $file) {
            $watcher->watchDir(dirname($file));
            $watcher->watchFile($file);
        }

        $watcher->watchFile($target);
        $watcher->watch();
    }
    
    public function getRelativePath($dir1, $dir2=NULL)
    {
        if (empty($dir2)) {
            $dir2 = $this->config->getLoader();
        }

        return Path::getRelative($dir1, $dir2);
    }

    public function getClassMapper(Array $map)
    {
        $docs = array();
        foreach ($map as $doc) {
            unset($doc['annotation']);
            $docs[$doc['class']] = $doc;
        }

        return $docs;
    }
}
