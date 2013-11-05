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

    public function __construct(Configuration $config, Watch $watcher)
    {
        $annotations  = new Notoj\Annotations;
        $this->files  = array();
        $this->config = $config;

        foreach ($config->getModelPath() as $path) {
            $dir = new Notoj\Dir($path);
            $dir->getAnnotations($annotations);
            $this->files = array_merge($this->files, $dir->getFiles());
        }

        foreach (array('Filter', 'Plugin') as $d) {
            $dir = new Notoj\Dir(__DIR__ . "/$d");
            $dir->getAnnotations($annotations);
        }

        $docs     = [];
        $parents  = [];
        $refCache = [];
        foreach ($annotations->get('Persist') as $object) {
            if (!$object->isClass()) continue;
            $class = strtolower($object['class']);
            $parents[$class]  = $object;
            $refCache[$class] = [];
            if ($object->has('RefCache')) {
                foreach ($object->get('RefCache') as $args) {
                    $args = $args['args'];
                    if (empty($args)) {
                        throw new \Exception("@RefCache expects at least one argument");
                    }
                    foreach ($args as $p) {
                        $refCache[$class][] = $p;
                    }
                }
            }
        }

        foreach (array('Persist', 'Embeddable') as $type) {
            foreach ($annotations->get($type) as $object) {
                if (!$object->isClass()) continue;
                $parent = $object->getParent();

                $data = array(
                    'class' => strtolower($object['class']), 
                    'file'  => $this->getRelativePath($object['file']),
                    'annotation' => $object,
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
                        if (empty($parents[$class])) break;
                        if ($parents[$class]->has('SingleCollection') || empty($data['name'])) {
                            $data['name'] = current($parents[$class]->getOne('Persist'));
                            $data['disc'] = $parents[$class]->getOne('SingleCollection') ?: ['__type'];
                            $data['disc'] = current($data['disc']);
                        }
                        $parent = $parent->GetParent();
                    } 

                    if (empty($data['name'])) continue;

                    if (!empty($data['disc'])) {
                        // give them some weird name
                        $docs[$data['class']] = $data;
                    } else {
                        $docs[$data['name']] = $data;
                    }
                }
            }
        }

        $files = array();
        $read  = [
            'Validate' => 'validators', 'Hydratate' => 'hydratations',
            'DefaultValue' => 'defaults',
        ];
        foreach ($read as $operation => $var) {
            $$var = array();
            foreach ($annotations->get($operation) as $validator) {
                foreach ($validator->get($operation) as $val) {
                    $type = current($val['args']);
                    if (empty($type)) continue;
                    if ($validator->isMethod()) {
                        ${$var}[$type] = "\\" . $validator['class'] . "::" . $validator['function'];
                    } else if ($validator->isFunction()) {
                        ${$var}[$type] = "\\" . $validator['function'];
                    }
                    $files[$type] = $this->getRelativePath($validator['file']);
                }
            }
        }

        $namespace    = sha1($config->getLoader());
        $mapper       = $this->getDocumentMapper($docs);
        $class_mapper = $this->getClassMapper($docs);
        $events       = array(
            'preSave', 'postSave', 'preCreate', 'postCreate', 'onHydratation', 
            'preUpdate', 'postUpdate', 'preDelete', 'postDelete'
        );
        $indexes      = array();
        $plugins      = array();

        foreach ($annotations->get('Plugin') as $prop) {
            if (!$prop->isClass()) continue;
            foreach ($prop->get('Plugin') as $anno) {
                $name = current($anno['args']);
                if (empty($name)) continue;
                $plugins[$name] = $prop;
            }
        }

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

        $references = [];
        $vars = [
            'Reference' => false,
            'ReferenceOne' => false,
            'ReferenceMany' => true,
        ];

        foreach ($vars as $type => $multi) {
            foreach ($annotations->get($type) as $prop) {
                if (!$prop->isProperty()) continue;
                foreach ($prop as $id => $ann) {
                    if (Empty($ann['args'])) continue;
                    $pzClass = strtolower($prop['class']);
                    $pzArgs  = !empty($ann['args'][1]) ? $ann['args'][1] :[];
                    if (empty($docs[$ann['args'][0]])) {
                        // Document is not found, probably there
                        // are inheritance
                        foreach ($docs as $doc) {
                            if ($doc['name'] != $ann['args'][0]) {
                                continue;
                            }
                            $pxClass = $doc['class'];
                            $pxArgs  = $pzArgs;
                            if (!empty($refCache[$pxClass])) {
                                $pxArgs = array_unique(array_merge($refCache[$pxClass], $pzArgs));
                            }

                            if (empty($pxArgs)) continue;

                            $references[$pxClass][] = array(
                                'class'         => $pzClass,
                                'property'      => $prop['property'],
                                'target'        => $class_mapper[$pxClass]['name'],
                                'collection'    => $class_mapper[strtolower($prop['class'])]['name'],
                                'update'        => $pxArgs,
                                'multi'         => $multi,
                                'deferred'      => $prop->has('Deferred'),
                            );
                        }
                        continue;
                    }

                    $pxClass = $docs[$ann['args'][0]]['class'];

                    if (!empty($refCache[$pxClass])) {
                        $pzArgs = array_unique(array_merge($refCache[$pxClass], $pzArgs));
                    }

                    if (empty($pzArgs)) continue;

                    $references[$pxClass][] = array(
                        'class'         => $pzClass,
                        'property'      => $prop['property'],
                        'target'        => $class_mapper[$pxClass]['name'],
                        'collection'    => $class_mapper[strtolower($prop['class'])]['name'],
                        'update'        => $pzArgs,
                        'multi'         => $multi,
                        'deferred'      => $prop->has('Deferred'),
                    );
                }
            }
        }   

        $self = $this;
        $code = Templates::get('documents')
            ->render(compact(
                'docs', 'namespace', 'class_mapper', 'events',
                'validators', 'mapper', 'files', 'indexes',
                'plugins', 'hydratations', 'self', 'references',
                'defaults', 'refCache'
            ), true);

        $code = FixCode::fix($code);

        File::write($config->getLoader(), $code);

        $this->files = array_unique($this->files);

        foreach ($this->files as $file) {
            $watcher->watchDir(dirname($file));
            $watcher->watchFile($file);
        }

        $watcher->watch();
    }
    
    public function getRelativePath($dir1, $dir2=NULL)
    {
        if (empty($dir2)) {
            $dir2 = $this->config->getLoader();
        }

        return Path::getRelative($dir1, $dir2);
    }

    public function getDocumentMapper(Array $map)
    {
        $docs = array();
        foreach ($map as $key => $doc) {
            unset($doc['annotation']);
            $docs[$key] = $doc;
        }

        return $docs;
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
