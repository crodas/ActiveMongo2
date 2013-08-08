<?php

namespace ActiveMongo2\Generated{{$namespace}};

use ActiveMongo2\Connection;

class Mapper
{
    protected $mapper = {{ var_export($mapper, true) }};
    protected $class_mapper = {{ var_export($class_mapper, true) }};
    protected $connection;

    public function __construct(Connection $conn)
    {
        $this->connection = $conn;
    }

    public function mapCollection($col)
    {
        if (empty($this->mapper[$col])) {
            throw new \RuntimeException("Cannot map {$col} collection to its class");
        }

        return $this->mapper[$col];
    }

    public function mapClass($class)
    {
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->class_mapper[$class];
    }

    public function mapObject($object)
    {
        $class = get_class($object);
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->class_mapper[$class];
    }

    @foreach($docs as $doc)

    /**
     *  Validate {{$doc['class']}} object
     */
    public function validate_{{sha1($doc['class'])}}(\{{$doc['class']}} $object)
    {
        @foreach ($doc['annotation']->getProperties() as $prop)
            /** {{$prop['property']}} */
            @if (in_array('public', $prop['visibility']))
            if ($object->{{$prop['property']}}) {
                $data = $object->{{$prop['property']}};
            } else {
                @if ($prop->has('Required'))
                throw new \RuntimeException("{$prop['property']} cannot be empty");
                @else
                $data = NULL;
                @end
            }
            @else
            $property = new \ReflectionProperty($object, "{{ $prop['property'] }}");
            $property->setAccessible(true);
            $data = $property->getValue($object);
            @if ($prop->has('Required'))
            if (empty($data)) {
                throw new \RuntimeException("{$prop['property']} cannot be empty");
            }
            @end
            @end

            @foreach($validators as $name => $callback)
            @if ($prop->has($name))
            if ($data !== NULL && !{{$callback}}($data)) {
                throw new \RuntimeException("Validation failed for {{$name}}");
            }
            @end
            @end

        @end
    }

        @foreach ($events as $ev)
    /**
     *  Code for {{$ev}} events for objects {{$doc['class']}}
     */
    public function trigger_{{$ev}}_{{sha1($doc['class'])}}(\{{$doc['class']}} $document, Array $args)
    {
        @foreach($doc['annotation']->getMethods() as $method)
            @if ($method->has($ev)) 
                @if (in_array('public', $method['visibility']))
            $return = $document->{{$method['function']}}($document, $array, $this->conn, {{var_export($method[0]['args'], true)}});
                @else
            $reflection = new ReflectionMethod("\\{{addslashes($doc['class'])}}", "{{$method['function']}}");
            $return = $reflection->invoke($document, $document, $array, $this->conn, {{var_export($method[0]['args'], true)}});
                @end
            if ($return === FALSE) {
                throw new \RuntimeException("{{addslashes($doc['class']) . "::" . $method['function']}} returned false");
            }
            @end
        @end
    }

        @end
    @end
}
