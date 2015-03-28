<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2015 ActiveMongo                                                  |
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

class LockingException extends \RuntimeException
{
}

/**
 *  @Plugin(Locking)
 */
class Locking
{
    /** @onMapping */
    public static function onCompile($schema)
    {
        $schema->defineProperty('/** @Hidden @String */', '__ol_version');
    }

    /** @preCreate */
    public static function onCreate($doc, Array $args)
    {
        $args[0]['__ol_version'] = time() . '::' . uniqid(true);
    }

    /**
     *  @preUpdate
     */
    public static function prepareUpdate($doc, array $args)
    {
        $args[2]['__ol_version'] = $doc->__ol_version; /* Change the update query to include the version */
        $args[0]['$set']['__ol_version'] = time() . '::' . uniqid(true);
        $args[3] = 1; /* $w */

        if (count($args[0]) > 1) {
            /* Make sure we execute $set at the end of the stack */
            $set = $args[0]['$set'];
            unset($args[0]['$set']);
            $args[0]['$set'] = $set;
        }
    }

    protected static function analizeUpdate(Array $Changes, Array $current, Array $saved, $class)
    {
        $allowed  = ['updated' => 1, '__ol_version' => 1];
        foreach ($Changes as $op => $changes) {
            foreach ($changes as $column => $values) {
                if (empty($allowed[$column]) && !empty($current[$column]) && !empty($saved[$column]) && $current[$column] !== $saved[$column]) {
                    throw new LockingException("You cannot update stale document {$class} due column => {$column}");
                }
            }
        }
    }

    /**
     *  @postUpdate
     */
    public static function checkUpdate($doc, array $args, $conn, $xargs, $mapper, $class)
    {
        $response = current($args[3]);
        if ($response['n'] != 1) {
            self::analizeUpdate(
                $args[0],
                $mapper->getDocument($doc),
                $mapper->getDocument($conn->getCollection($class)->getById( $args[2] )),
                $class
            );
        }

        foreach ($args[0] as $op => $changes) {
            $args[4]->update(
                array('_id' => $args[2]),
                array($op => $changes), 
                array('w' => 1)
            );
        }
    }
}
