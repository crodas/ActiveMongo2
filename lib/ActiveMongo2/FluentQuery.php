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

class FluentQuery implements \IteratorAggregate
{
    protected $parent;
    protected $exprValue;
    protected $operator;
    protected $finalized;
    protected $col;
    protected $query = array();
    protected $update;
    protected $field;

    public function __construct(Collection $col)
    {
        $this->col = $col;
    }

    protected function finalizeChild($child)
    {
        if ($child->finalized) {
            return $this;
        }
        $child->finalized = true;
        $op = $child->operator;
        if ($child->exprValue) {
            if ($op == '$pull') {
                $this->genericUpdate($op, $child->query['$expr']);
            } else {
                $this->genericQuery($op, $child->query['$expr']);
            }
            return $this;
        }
        if (empty($this->query[$op])) {
            $this->query[$op] = array();
        }
        $this->query[$op][] = $child->query;
        return $this;
    }
    
    protected function createChild($op)
    {
        if ($this->exprValue) {
            return $this->end()->createChild($op);
        }
        $expr = new self($this->col);
        $expr->parent   = $this;
        $expr->operator = $op;
        return $expr;
    }

    public function end()
    {
        if (empty($this->parent)) {
            throw new \Exception("You cannot call to end()");
        }
        return $this->parent->finalizeChild($this);
    }

    public function addNor()
    {
        return $this->createchild('$nor');
    }

    public function addAnd()
    {
        return $this->createchild('$and');
    }

    public function addOr()
    {
        return $this->createchild('$or');
    }

    public function not()
    {
        $not = $this->createChild('$not');
        $not->exprValue = true;
        $not->field = '$expr';
        return $not;
    }

    public function getQuery()
    {
        if ($this->parent) {
            return $this->end()->getQuery();
        }
        return $this->query;
    }

    public function getUpdate()
    {
        if ($this->parent) {
            return $this->end()->getUpdate();
        }
        return $this->update;
    }

    protected function assertField()
    {
        if (empty($this->field)) {
            throw new \RuntimeException("You need to call ->field first");
        }
    }

    protected function genericUpdate($op, $value)
    {
        $this->assertField();
        if (empty($this->update[$op])) {
            $this->update[$op] = array();
        }
        $this->update[$op][$this->field] = $value;
        return $this;
    }


    protected function genericQuery($op, $value)
    {
        if (empty($this->field)) {
            throw new \RuntimeException("You need to call ->field first");
        }
        if (empty($this->query[$this->field])) {
            $this->query[$this->field] = array();
        }
        $this->query[$this->field][$op] = $value;

        return $this;
    }

    public function in(Array $values)
    {
        return $this->genericQuery('$in', $value);
    }

    public function all(Array $values)
    {
        return $this->genericQuery('$all', $value);
    }

    public function notIn(Array $values)
    {
        return $this->genericQuery('$nin', $value);
    }

    public function equals($value)
    {
        $this->assertField();
        if ($this->exprValue) {
            throw new \Exception("Invalid call, please use ->notEquals(\$value) instead");
        }
        $this->query[$this->field] = $value;
        return $this;
    }

    public function notEquals($values)
    {
        return $this->genericQuery('$ne', $value);
    }

    public function greaterThan($value)
    {
        return $this->genericQuery('$gt', $value);
    }

    public function greaterThanOrEq($value)
    {
        return $this->genericQuery('$gte', $value);
    }

    public function lowerThan($value)
    {
        return $this->genericQuery('$lt', $value);
    }

    public function lowerThanOrEq($value)
    {
        return $this->genericQuery('$lte', $value);
    }

    public function range($min, $max)
    {
        $this->genericQuery('$gte', $min);
        $this->genericQuery('$lte', $max);
        return $this;
    }

    public function exists($exists = true)
    {
        return $this->genericQuery('$exists', (bool)$value);
    }

    public function mod($value)
    {
        return $this->genericQuery('$mod', $value+0);
    }

    public function type($value)
    {
        return $this->genericQuery('$type', $value+0);
    }

    public function size($value)
    {
        return $this->genericQuery('$size', (int)$value);
    }

    public function set($value)
    {
        return $this->genericUpdate('$set', $value);
    }

    public function inc($value = 1)
    {
        return $this->genericUpdate('$inc', $value+0);
    }

    public function rename($name)
    {
        return $this->genericUpdate('$rename', $name);
    }

    public function addToSet($value)
    {
        if (is_array($value)) {
            $value = array('$each' => array_values($value));
        }
        return $this->genericUpdate('$addToSet', $value);
    }

    public function push($value)
    {
        if (is_array($value)) {
            $value = array('$each' => array_values($value));
        }
        return $this->genericUpdate('$push', $value);
    }

    public function pull($value)
    {
        return $this->genericUpdate(is_array($value) ? '$pullAll' : '$pull', $value);
    }

    public function pullExpr()
    {
        $not = $this->createChild('$pull');
        $not->exprValue = true;
        $not->field = '$expr';
        return $not;
    }

    public function pop($end = true)
    {
        return $this->genericUpdate('$pop', $end ? 1 : -1);
    }

    public function unsetField()
    {
        return $this->genericUpdate('$unset', 1);
    }

    public function field($name)
    {
        if ($this->exprValue) {
            return $this->end()->field($name);
        }
        $this->field = $name;
        return $this;
    }

    public function getIterator()
    {
        if ($this->parent) {
            return $this->end()->getIterator();
        }
        return $this->col->find($this->query);
    }

    public function execute()
    {
        if ($this->parent) {
            return $this->end()->execute();
        }
        if (!empty($this->update)) {
            return $this->col->update($this->query, $this->update);
        }

        return $this->col->find($this->query);
    }

    public function first()
    {
        if ($this->parent) {
            return $this->end()->first();
        }
        if (!empty($this->update)) {
            throw new \RuntimeException("You cannot use first() with updates");
        }

        return $this->col->findOne($this->query);
    }

    public function count()
    {
        if ($this->parent) {
            return $this->end()->count();
        }
        return $this->col->count($this->query);
    }

    public function delete()
    {
        if ($this->parent) {
            return $this->end()->delete();
        }
        return $this->col->delete($this->query);
    }
}
