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

    protected $operations = array(
        'withChild' => array(
            'addNor' => '$nor',
            'addAnd' => '$and',
            'addOr'  => '$or',
        ),
        'withExpr' => array(
            'not' =>  '$not'
        ),
        'withValue' => array(
            'in'    => '$in',
            'all'   => '$all',
            'notIn' => '$nin',
            'notEquals'         => '$ne',
            'greaterThan'       => '$gt',
            'greaterOrEqThan'   => '$gte',
            'greaterThanOrEq'   => '$gte',
            'lowerThan'         => '$le',
            'lowerOrEqThan'     => '$lte',
            'lowerThanOrEq'     => '$lte',
            'range'     => array('$gte', '$lte'),
            'equals'    => '$eq',
            'eq'        => '$eq',
        ),
        'withTypedValue' => array(
            'type'      => array('int', '$type'),
            'exists'    => array('boolean', '$exists'),
            'mod'       => array('int', '$mod'),
            'size'      => array('int', '$size'),

        ),

        'updateWithValue' => array(
            'set'       => '$set',
            'rename'    => '$rename',
        ),
        
        'updateWithDefValue' => array(
            'inc'           => array(1, '$inc'),
            'unset'         => array(1, '$unset'),
            'unsetField'    => array(1, '$unset'),
        ),

        'updateArrayOrScalar' => array(
            'addToSet'  => '$addToSet', 
            'push'      => '$push',
        ),
    );

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

        if ($op == '$eq') {
            $this->query[$this->field] = $value;
            return $this;
        } 
        
        if (empty($this->query[$this->field])) {
            $this->query[$this->field] = array();
        }
        $this->query[$this->field][$op] = $value;

        return $this;
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

    public function remove($justOne = false)
    {
        if ($this->parent) {
            return $this->end()->remove($justOne);
        }
        if (!empty($this->update)) {
            throw new \Exception("Invalid call to remove(), it was expecting update (`execute()`)");
        }
        return $this->col->remove($this->query, compact('justOne'));
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

    protected function withChild($rule, $args)
    {
        return $this->createchild($rule);
    }

    protected function withTypedValue($rules, $args)
    {
        $value = current($args);
        settype($value, $rules[0]);
        $this->genericQuery($rules[1], $value);
        return $this;
    }


    protected function withValue($rules, $args)
    {
        foreach ((array)$rules as $i => $rule) {
            $this->genericQuery($rule, $args[$i]);
        }

        return $this;
    }

    protected function updateWithDefValue($rules, $args)
    {
        $value = current($args);
        $value = $value ? $value : $rules[0];
        return $this->genericUpdate($rules[1], $value);
    }

    protected function updateWithValue($rules, $args)
    {
        $value = current($args);
        return $this->genericUpdate($rules, $value);
    }

    public function __call($name, $args)
    {
        $name = strtolower($name);
        foreach ($this->operations as $rule => $rules) {
            foreach ($rules as $method => $operation) {
                if (strtolower($method) == $name) {
                    return $this->$rule($operation, $args);
                }
            }
        }

        throw new \RuntimeException("Invalid call to $name");
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
}
