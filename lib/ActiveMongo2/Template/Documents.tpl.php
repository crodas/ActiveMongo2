<?php

namespace ActiveMongo2\Generated{{$namespace}};

class Mapper
{
    protected $mapper = {{ var_export($docs, true) }};
    protected $class_mapper = {{ var_export($class_mapper, true) }};

    public function mapCollection($col)
    {
        if (empty(self::$mapper[$col])) {
            throw new \RuntimeException("Cannot collection {$col} to its collection");
        }

        return self::$mapper[$col];
    }

    public function mapClass($class)
    {
        if (empty(self::$class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return self::$class_mapper[$class];
    }

    public function mapObject($object)
    {
        $class = get_class($object);
        if (empty(self::$class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return self::$class_mapper[$class];
    }

    @foreach($docs as $doc)

    /**
     *  Validate {{$doc['class']}} object
     */
    public function validate_{{sha1($doc['class'])}}(\{{$doc['class']}} $object)
    {
        @foreach ($doc['annotation']->getProperties() as $prop)
            @if (in_array('public', $prop['visibility']))
            $data = $object->{{$prop['property']}};
            @else
            $property = new \ReflectionProperty($object, "{{ $prop['property'] }}");
            $property->setAccessible(true);
            $data = $property->getValue($object);
            @end
        @end
    }

        @foreach ($events as $ev)
    /**
     *  Code for {{$ev}} events for objects {{$doc['class']}}
     */
    public function trigger_{{$ev}}_{{sha1($doc['class'])}}($args)
    {
    }

        @end
    @end
}
