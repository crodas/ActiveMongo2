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

use Notoj\Notoj;
use WatchFiles\Watch;

class Configuration
{
    protected $loader;
    protected $path;
    protected $devel = false;
    protected $generated = false;
    protected $cache;
    protected $default = array('w' => 1);
    protected $failOnMissRef = true;

    public function __construct($loader)
    {
        $this->loader = $loader;
        $this->cache  = new Cache\Cache;
    }

    public function failOnMissingReference($fail = null)
    {
        if ($fail === null) {
            return $this->failOnMissRef;
        }
        $this->failOnMissRef = (bool)$fail;
        return $this;
    }

    public function setWriteConcern($w)
    {
        $this->default['w'] = $w;
        return $this;
    }

    public function getWriteConcern()
    {
        return $this->default['w'];
    }

    public function setCacheStorage(Cache\Storage $storage)
    {
        $this->cache->setStorage($storage);
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function getModelPath()
    {
        return $this->path;
    }

    public function getLoader()
    {
        return $this->loader;
    }

    public function addModelPath($path)
    {
        $this->path[] = $path;
        return $this;
    }

    protected function generateIfNeeded()
    {
        if ($this->devel || !is_file($this->loader)) {
            Notoj::enableCache($this->loader . ".tmp");
            $watcher = new Watch($this->loader . ".lock");
            if ($watcher->hasChanged()) {
                new Generate($this, $watcher);
                $this->generate = true;
            }
        }
    }

    public function hasGenerated()
    {
        return $this->generated;
    }

    public function initialize(Connection $conn)
    {
        $this->generateIfNeeded();
        $class = "\\ActiveMongo2\\Generated" . sha1($this->GetLoader()) . "\\Mapper";
        if (!class_exists($class, false)) {
            require $this->getLoader();
        }
        return new $class($conn);
    }

    public function development()
    {
        $this->devel = true;
        return $this;
    }
}
