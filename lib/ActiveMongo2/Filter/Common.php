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

/**
 *  @DataType date
 *  @Validate(Date)
 *  @Embed
 */
function is_date(&$date)
{
    /* is_date */
    if ($date instanceof \MongoDate) {
        return true;
    }
    if ($date instanceof \Datetime) {
        $date = $date->getTimestamp();
    }
    if (is_string($date)) { 
        $date = strtotime($date);
    }
    if (is_integer($date) && $date > 0) {
        $date = new \MongoDate($date);
        return true;
    }
    return false;
}

/**
 *  @Validate(Hash)
 *  @DataType hash
 *  @Embed
 */
function is_hash($obj)
{
    if (is_array($obj)) {
        $diff = array_diff(array_keys($obj), range(0, count($obj)-1));
        return !empty($diff);
    }
    return false;
}

/** 
 * @Validate(String) 
 * @DataType String
 * @Embed
 */
function _validate_string(&$value)
{
    if (!is_scalar($value)) {
        return false;
    }
    $value = "" . $value;
    return true;
}

/** 
 * @Validate(Geo) 
 * @Validate(Location)
 */
function _validate_geo(&$values)
{
    if (!is_array($values)) {
        return false;
    }
    $value = array_values($values);
    if (count($values) != 2 || !is_numeric($values[0]) || !is_numeric($values[1])) {
        return false;
    }
}

/** 
 * @Validate(Bool) 
 * @Validate(Boolean) 
 * @DataType Boolean
 * @Embed
 */
function _validate_boolean(&$value)
{
    $value = (bool)$value;
    return true;
}


/** 
 * @Validate(Integer) 
 * @Validate(Int) 
 * @DataType Int
 * @Embed
 */
function _validate_integer(&$value)
{
    if (!is_numeric($value)) {
        return false;
    }
    $value = (int)$value;
    return true;
}

/** 
 * @Validate(Numeric)
 * @DataType Numeric
 * @Embed
 */
function _validate_numeric(&$value)
{
    if (!is_numeric($value)) {
        return false;
    }
    $value = $value+0;
    return true;
}

/** 
 * @Validate(Float)
 * @DataType Float
 * @Embed
 */
function _validate_float(&$value)
{
    if (!is_numeric($value)) {
        return false;
    }
    $value = (float)$value;
    return true;
}


/**
 *  @Validate(Password)
 *  @DataType Password
 *  @Embed
 */
function _validate_password(&$value, $args)
{
    if (!password_get_info($value)['algo']) {
        $value = password_hash($value, PASSWORD_BCRYPT, ["cost" => 7, "salt" => sha1(implode(",", $args))]);
    }
    return true;
}

/** 
 * @Hydratate(Array) 
 */
function _hydrate_array(&$value)
{
    foreach ($value as &$val) {
        if (!empty($val['__instance'])) {
            unset($val['__instance']);
        }
    }
    return $value;
}

/** 
 * @Validate(Array) 
 * @DataType Array
 * @Embed
 */
function _validate_array(&$value)
{
    if (!is_array($value)) {
        return false;
    }
    foreach ($value as &$v) {
        if (is_hash($v) && empty($v['__instance'])) {
            $v['__instance'] = uniqid(true);
        }
    }
    return true;
}

