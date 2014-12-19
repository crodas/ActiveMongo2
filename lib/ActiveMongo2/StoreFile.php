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

use MongoGridFS;
use MongoGridFSFile;

class StoreFile extends Connection
{
    protected $gridfs;
    protected $metadata;
    protected $object;
    protected $conn;
    protected $mapper;

    public function __construct(MongoGridFS $col, Array $document, $conn, &$object, $mapper)
    {
        $this->gridfs   = $col;
        $this->metadata = $document;
        $this->conn     = $conn;
        $this->object   = &$object;
        $this->mapper   = $mapper;
    }

    public function storeFile($name)
    {
        if (!is_file($name)) {
            throw new \RuntimeException("Cannot find file {$name}");
        }
        $this->gridfs->storeFile($name, $this->metadata);
        $doc = $this->gridfs->findOne($this->metadata);
        $this->mapper->populate($this->object, $doc);
    }

    public function storeBytes($bytes)
    {
        $this->gridfs->storeBytes($bytes, $this->metadata);
        $doc = $this->gridfs->findOne($this->metadata);
        $this->mapper->populate($this->object, $doc);
    }

    public function storeUpload($name)
    {
        if (empty($_FILES[$name])) {
            throw new \RuntimeException("Cannot find \$_FILE[{$name}]");
        }
        $this->gridfs->storeUpload($name, $this->metadata);
        $doc = $this->gridfs->findOne($this->metadata);
        $this->mapper->populate($this->object, $doc);
    }
}
