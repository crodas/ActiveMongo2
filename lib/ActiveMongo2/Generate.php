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
use crodas\Build;
use crodas\SimpleView\FixCode;
use crodas\FileUtil\File;
use crodas\FileUtil\Path;

class Generate
{
    protected $files = array();
    protected static $cdir;

    protected function writeFileWatch(Build $builder, Generate\Collections $collections)
    {
        foreach ($collections->getFiles() as $file) {
            $builder->watch($file);
            $builder->watch(dirname($file));
        }
    }

    public function __construct(Array $config, Build $builder)
    {
        $collections  = new Generate\Collections($config['files'], $this);
        self::$cdir   = $config['loader'];

        $namespace = "ActiveMongo2\\Namspace" . uniqid(true); 
        $rnd       = uniqid();
        $valns     = $collections->getValidatorNS();
        $validator = $collections->getValidator();

        class_exists("crodas\FileUtil\File"); // preload class

        $args = compact('docs', 'namespace','mapper', 'indexes', 'self', 'collections', 'valns', 'rnd', 'validator');
        $code = Template\Templates::get('documents')
            ->render($args, true);

        if (strlen($code) >= 1024*1024) {
            File::write($config['loader'], $code);
        } else {
            File::write($config['loader'], FixCode::fix($code));
        }
        $this->writeFileWatch($builder, $collections);
    }
    
    public static function getRelativePath($dir1, $dir2=NULL)
    {
        if (empty($dir2)) {
            $dir2 = self::$cdir;
        }

        if (substr($dir1, 0, 4) == "/../") {
            // already relative path
            return $dir1;
        }

        return Path::getRelative($dir1, $dir2);
    }
}
