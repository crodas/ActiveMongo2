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

namespace ActiveMongo2\Plugin;

use ActiveMongo2\Reference;

/**
 *  @Hydratate(ReferenceMany)
 */
function _hydratate_reference_many(&$value, Array $args, $conn, $mapper)
{
    foreach ((array)$value as $id => $val) {
        _hydratate_reference_one($value[$id], $args, $conn, $mapper);
    }
}

/**
 *  @Validate(ReferenceMany)
 */
function _validate_reference_many(&$value, Array $args, $conn, $mapper)
{
    if (!is_array($value)) {
        return false;
    }

    foreach ($value as $id => $val) {
        if (!_validate_reference_one($value[$id], $args, $conn, $mapper)) {
            return false;
        }
    }

    return true;
}


/**
 *  @Hydratate(Reference)
 *  @Hydratate(ReferenceOne)
 */
function _hydratate_reference_one(&$value, Array $args, $conn, $mapper)
{
    $expected = current($args);
    if ($expected && $expected != $value['$ref']) {
        throw new \RuntimeException("Expecting document {$expected} but got {$value['ref']}");
    }

    $class = $mapper->mapCollection($value['$ref'])['class'];
    $value = new Reference($value, $class, $conn, empty($value['_data']) ? array() : $value['_data']);
}

/**
 *  @Validate(Reference)
 *  @Validate(ReferenceOne)
 */
function _validate_reference_one(&$value, Array $args, $conn, $mapper)
{
    $document = $value;
    $conn->save($document);

    if (!empty($args) && !$conn->is(current($args), $document)) {
        throw new \RuntimeException("Invalid value");
    }

    if ($document instanceof Reference) {
        $value = $document->getReference();
    } else {
        $array = $mapper->validate($document);
        $value = array(
            '$id'   => $array['_id'],
            '$ref'  => $mapper->mapClass(get_class($document))['name'],
            '__uniqid'   => uniqid(true),
        );
    }

    return true;
}
