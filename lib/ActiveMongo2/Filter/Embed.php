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

namespace ActiveMongo2\Filter;

/**
 *  @Hydratate(EmbedOne)
 *  @Hydratate(Embed)
 */
function _populate_embed_one(&$value, $args, $conn, $mapper)
{
    if (is_array($value) && $value)  {
        $class = $value['__embed_class'];
        $value = $conn->registerDocument($class, $value);
    }
}

/**
 *  @Hydratate(EmbedMany)
 */
function _populate_embed_many(&$value, $args, $conn, $mapper)
{
    foreach ((array)$value as $id => $val) {
        _populate_embed_one($value[$id], $args, $conn, $mapper);
    }
}

/**
 *  @Validate(EmbedMany)
 *  @DataType Array
 */
function _do_embed_many(&$value, $zargs, $conn, $args, $mapper)
{
    if (!is_array($value)) {
        return false;
    }

    foreach ($value as $id => $val) {
        if (!_do_embed_one($value[$id], $zargs, $conn, $args, $mapper)) {
            return false;
        }
    }

    return _validate_array($value, $zargs, $conn, $args, $mapper);
}

/**
 *  @Validate(EmbedOne)
 *  @Validate(Embed)
 *  @DataType Hash
 */
function _do_embed_one(&$value, $zargs, $conn, $args, $mapper)
{
    if (!is_object($value)) {
        return false;
    }

    $class = $mapper->get_class($value);

    if ($args) {
        $collection = current($args); 
        if ($class != $mapper->mapCollection($collection)['class']) {
            return false;
        }
    }

    $value = $mapper->validate($value);
    $value['__embed_class'] = $class;
    return true;
}
