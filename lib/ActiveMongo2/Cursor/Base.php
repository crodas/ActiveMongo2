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
namespace ActiveMongo2\Cursor;

use MongoCollection;
use MongoCursor;

trait Base
{
    public function getResultCache()
    {
        $cache = array();
        $this->rewind();
        while ($this->valid()) {
            $cache[$this->key()] = parent::current();
            $this->next();
        }
        reset($this);
        return $cache;
    }

    public function  paginate($page, $perPage)
    {
        if (array_key_exists($page, $_REQUEST)) {
            $page = $_REQUEST[$page];
        }
        $page  = max(1,  (int)$page);
        $total = $this->count(); 
        $this->skip(($page-1)*$perPage)
            ->limit($perPage);

        $pages = range(1, ceil($total/$perPage));
        $pages = array_merge(
            array_slice($pages, max(0,$page-3), 10),
            array_slice($pages, -2)
        );

        return array('pages' => array_unique($pages), 'current' => $page);
    }

    public function first()
    {
        $current = parent::current();
        return $this->current();
    }

    public function current()
    {
        $current = parent::current();
        if (empty($current)) {
            $current = $this->GetNext();
        }
        $class   = $this->mapper->getObjectClass($this->col, $current);
        if ($this->col instanceof \MongoGridFs) {
            $current = new \MongoGridFsFile($this->col, $current);
        }
        return $this->zcol->registerDocument($current);
    }

    public function toArray()
    {
        return iterator_to_array($this);
    }
}
