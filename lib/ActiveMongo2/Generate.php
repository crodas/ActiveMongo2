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
        $this->files  = array();
        $this->config = $config;
        $self = $this;

        $collections = new Generate\Collections((array)$config->getModelPath(), $this);
        $fixPath = function($value) use ($self) {
            $value->setPath($self->getRelativePath($value->getPath()));
        };
        $collections->map($fixPath);
        array_map($fixPath, $collections->getDefaults());
        array_map($fixPath, $collections->getPlugins());
        array_map($fixPath, $collections->getHydratators());
        array_map($fixPath, $collections->getValidators());

        $target       = $config->getLoader();
        $namespace    = sha1($target);

        $self = $this;
        $code = Template\Templates::get('documents')
            ->render(compact(
                'docs', 'namespace',
                'mapper', 'indexes', 'self',
                'collections'
            ), true);

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

        if (substr($dir1, 0, 4) == "/../") {
            // already relative path
            return $dir1;
        }

        return Path::getRelative($dir1, $dir2);
    }
}
