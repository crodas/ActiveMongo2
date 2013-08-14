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

class Generate
{
    protected $files = array();

    public function __construct(Configuration $config, Watch $watcher)
    {
        $annotations = new Notoj\Annotations;
        $this->files = array();
        foreach ($config->getModelPath() as $path) {
            $dir = new Notoj\Dir($path);
            $dir->getAnnotations($annotations);
            $this->files = array_merge($this->files, $dir->getFiles());
        }

        foreach (array('Filter', 'Plugin') as $d) {
            $dir = new Notoj\Dir(__DIR__ . "/$d");
            $dir->getAnnotations($annotations);
        }

        $docs = array();
        foreach ($annotations->get('Persist') as $object) {
            if (!$object->isClass()) continue;
            $data = array(
               'class' => $object['class'], 
               'file' => $object['file'],
               'annotation' => $object,
            );

            foreach ($object->get('Persist') as $ann) {
                $data['name'] = current($ann['args']);
                if (empty($data['name'])) continue;

                $docs[ $data['name'] ] = $data;
            }
        }

        $files  = array();
        foreach (array('Validate' => 'validators', 'Hydratate' => 'hydratations') as $operation => $var) {
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
                    $files[$type] = $validator['file'];
                }
            }
        }

        $namespace    = sha1($config->getLoader());
        $mapper       = $this->getDocumentMapper($docs);
        $class_mapper = $this->getClassMapper($docs);
        $events       = array('preSave', 'postSave', 'preCreate', 'postCreate', 'onHydratation', 'preUpdate', 'postUpdate');
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
            $collection = $class_mapper[$prop['class']]['name'];
            foreach ($prop->get('Unique') as $anno) {
                $indexes[] = array($collection,  array($prop['property'] => 1), array('unique' => 1));
            }
        }

        $code = Templates::get('documents')
            ->render(compact(
                'docs', 'namespace', 'class_mapper', 'events',
                'validators', 'mapper', 'files', 'indexes',
                'plugins', 'hydratations'
            ), true);

        $code = FixCode::fix($code);

        if (file_put_contents($config->getLoader(), $code, LOCK_EX) === false) {
            throw new \RuntimeException("Cannot write file " . $config->GetLoader());
        }

        $this->files = array_unique($this->files);

        foreach ($this->files as $file) {
            $watcher->watchDir(dirname($file));
            $watcher->watchFile($file);
        }

        //$watcher->watch();
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
