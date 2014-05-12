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
use crodas\FileUtil\File;
use crodas\FileUtil\Path;

class Generate
{
    protected $files = array();
    protected $config;

    protected function fixPath(Generate\Collections $collections)
    {
        $self = $this;
        $fixPath = function($value) use ($self) {
            $value->setPath($self->getRelativePath($value->getPath()));
        };
        $collections->map($fixPath);
        array_map($fixPath, $collections->getAnnotationByName('DefaultValue'));
        array_map($fixPath, $collections->getAnnotationByName('Plugin'));
        array_map($fixPath, $collections->getAnnotationByName('Hydratate'));
        array_map($fixPath, $collections->getAnnotationByName('Validate'));
    }

    protected function writeFileWatch(Watch $watcher, Generate\Collections $collections)
    {
        foreach ($collections->getFiles() as $file) {
            $watcher->watchDir(dirname($file));
            $watcher->watchFile($file);
        }

        $watcher->watchFile($this->config->getLoader());
        $watcher->watch();
    }

    public function __construct(Configuration $config, Watch $watcher)
    {
        $this->config = $config;

        $collections = new Generate\Collections((array)$config->getModelPath(), $this);
        $this->fixPath($collections);

        $target    = $config->getLoader();
        $namespace = sha1($target);
        $valns     = $collections->getValidatorNS();

        $args = compact('docs', 'namespace','mapper', 'indexes', 'self', 'collections', 'valns');
        $code = Template\Templates::get('documents')
            ->render($args, true);

        $code .= $collections->getValidatorCode();

        File::write($target, FixCode::fix($code));
        $this->writeFileWatch($watcher, $collections);
    }
    
    public function getRelativePath($dir1, $dir2=NULL)
    {
        if (empty($dir2)) {
            $dir2 = $this->config->getLoader();
        }

        if (substr($dir1, 0, 4) == "/../") {
            // already relative path
            return $dir1;
        }

        return Path::getRelative($dir1, $dir2);
    }
}
