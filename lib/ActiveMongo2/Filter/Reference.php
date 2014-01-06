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

use ActiveMongo2\Reference;

require_once __DIR__ . "/Common.php";

/**
 *  @Hydratate(ReferenceMany)
 */
function _hydratate_reference_many(&$value, Array $args, $conn, $unused, $mapper)
{
    foreach ((array)$value as $id => $val) {
        _hydratate_reference_one($value[$id], $args, $conn, $unused, $mapper);
    }
}

/**
 *  @Validate(ReferenceMany)
 *  @DataType Array
 */
function _validate_reference_many(&$value, Array $zargs, $conn, $args, $mapper)
{
    if (!is_array($value)) {
        return false;
    }

    foreach ($value as $id => $val) {
        if (!_validate_reference_one($value[$id], $zargs, $conn, $args, $mapper)) {
            return false;
        }
    }

    return _validate_array($value, $zargs, $conn, $args, $mapper);
}


/**
 *  @Hydratate(Reference)
 *  @Hydratate(ReferenceOne)
 *  @DataType Hash
 */
function _hydratate_reference_one(&$value, Array $args, $conn, $unused, $mapper)
{
    $expected = current($args);
    if ($expected && $expected != $value['$ref'] && !empty($value['__class']) && $expected != $value['__class']) {
        throw new \RuntimeException("Expecting document {$expected} but got {$value['$ref']}");
    }

    try {
        $class = $mapper->mapCollection($value['$ref'])['class'];
    } catch (\Exception $e) {
        if (empty($value['__class'])) {
            throw new \RuntimeException("reference of {$value['$ref']} needs __class interal type");
        }
        $class = $value['__class'];
    }
    $value = new Reference($value, $class, $conn, $mapper->getMapping($class), $mapper);
    $mapper->trigger('onHydratation', $value);
}

/**
 *  @Validate(Reference)
 *  @Validate(ReferenceOne)
 */
function _validate_reference_one(&$value, Array $rargs, $conn, $args, $mapper)
{
    if ($value instanceof Reference) {
        $value = $value->getObjectOrReference();
        if (is_array($value)) {
            if (!empty($args[1])) {
                foreach ((array)$args[1] as $prop) {
                    if (!empty($array[$prop])) {
                        $value[$prop] = $array[$prop];
                    }
                }
            }
            return true;
        }
    }

    $document = $value;
    $info     = $mapper->mapClass($document);
    if (!$info['is_gridfs']) {
        $conn->save($document);
    }

    $check = !empty($args) ? current($args) : null;
    if ($check && !$document instanceof $check && !$conn->is(current($args), $document)) {
        throw new \RuntimeException("Invalid value");
    }
    
    $value = $mapper->getReference($document, empty($args[1]) ? [] : array_flip($args[1]));

    return true;
}
