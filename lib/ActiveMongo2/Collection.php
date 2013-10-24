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

use MongoCollection;

class Collection 
{
    protected $zconn;
    protected $class;
    protected $zcol;

    protected static $defaultOpts = array(
        'w' => 1,
        'multiple' => true,
    );

    public function __construct(Connection $conn, $class, MongoCollection $col)
    {
        $this->zconn  = $conn;
        $this->zcol   = $col;
        $this->class  = $class;
    }

    protected function analizeUpdate($query)
    {
    }

    public function query()
    {
        return new FluentQuery($this);
    }

    public function update($filter, $update, $opts = array())
    {
        $this->analizeUpdate($update);
        $opts = array_merge(self::$defaultOpts, $opts);
        return $this->zcol->update($filter, $update, $opts);
    }

    public function count($filter = array(), $skip = 0, $limit = 0)
    {
        return $this->zcol->count($filter, $skip, $limit);
    }

    public function drop()
    {
        $this->zcol->drop();
    }

    public function aggregate()
    {
        $document = call_user_func_array([$this->zcol, 'aggregate'], func_get_args());
        if (empty($document['ok'])) {
            throw new \RuntimeException($document['errmsg']);
        }

        $results = [];
        foreach ($document['result'] as $res) {
            $results[] = $this->zconn->registerDocument($this->getClass($res), $res);
        }

        return $results;
    }

    public function getClass(Array $doc)
    {
        if (!empty($this->class['class'])) {
            return $this->class['class'];
        }

        if (!empty($doc[$this->class['prop']])) {
            return $doc[$this->class['prop']];
        }

        throw new \RuntimeException("Cannot map document from {$this->zcol} to its class");
    }

    public function findAndModify($query, $update, $options)
    {
        $response = $this->zcol->findAndModify($query, $update, null, $options);

        return $this->zconn->registerDocument($this->getClass($response), $response);
    }

    public function find($query = array(), $fields = array())
    {
        return new Cursor($query, $fields, $this->zconn, $this->zcol, $this);
    }

    public function findOne($query = array(), $fields = array())
    {
        $doc =  $this->zcol->findOne($query, $fields);
        if (empty($doc)) {
            return $doc;
        }

        return $this->zconn->registerDocument($this->getClass($doc), $doc);
    }
}
