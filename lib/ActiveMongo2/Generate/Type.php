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

use Notoj\Annotation;
use ActiveMongo2\Template\Templates;

class Type extends Base
{
    protected $file;
    protected $type;

    public function __construct(Annotation $ann, $type)
    {
        $this->annotation = $ann;
        $this->type       = $type;
        $this->name       = $type;
    }

    public function getFunction()
    {
        return $this->annotation['function'];
    }

    public function getMethod()
    {
        return $this->getFunction();
    }

    protected function getFunctionBodyStart(&$name)
    {
        $parts = explode("\\", $this->annotation['function']);
        $name  = end($parts); 
        $lines = file($this->annotation['file']);
        return implode('', array_slice($lines, $this->annotation['line'] -1));
    }

    protected function getEmbeddableCode(&$code)
    {
        $code  = $this->getFunctionBodyStart($name);
        $start = strpos($code, '{', stripos($code, $name))+1; 
        $end   = $start;
        $max   = strlen($code);

        for ($i = 1; $i >  0 && $end < $max; $end++) {
            if ($code[$end] == '}') {
                $i--;
            } else if ($code[$end] == '{') {
                $i++;
            }
        }

        $code = substr($code, $start, $end - $start - 1);

        return $end < $max;
    }

    public function toEmbedCode($prop)
    {
        $this->getEmbeddableCode($code);
        $exit = "exit_" . uniqid(true);
        $code = "$code\n$exit:\n"; 
        $code = str_replace('$value', $prop, $code);
        $code = preg_replace_callback('/return([^;]+);/smU', function($args) use ($exit) {
            return "\$return = $args[1];
            goto $exit;";
        }, $code);

        $code = preg_replace("/goto $exit;\s+$exit:/smU", "", $code, -1, $done);
        if ($done && strpos($code, $exit)) {
            $code .= "$exit:";
        }

        return $code;
    }

    public function isEmbeddable()
    {
        return $this->annotation->has('Embed') && $this->getEmbeddableCode($code);
    }

    public function toCode($prop, $var = '$doc')
    {
        $self = $this;
        $args = (array)$prop->annotation->getOne($this->name);
        return Templates::get('callback')
            ->render(compact('args', 'prop', 'self', 'var'), true);
    }
}
