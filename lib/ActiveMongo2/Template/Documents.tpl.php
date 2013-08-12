<?php

namespace ActiveMongo2\Generated{{$namespace}};

use ActiveMongo2\Connection;

class Mapper
{
    protected $mapper = {{ var_export($mapper, true) }};
    protected $class_mapper = {{ var_export($class_mapper, true) }};
    protected $loaded = array();
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

        $data = $this->mapper[$col];

        if (empty($this->loaded[$data['file']])) {
            require_once $data['file'];
            $this->loaded[$data['file']] = true;
        }

        return $data;
    }

    public function mapClass($class)
    {
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        $data = $this->class_mapper[$class];

        if (empty($this->loaded[$data['file']])) {
            require_once $data['file'];
            $this->loaded[$data['file']] = true;
        }

        return $data;
    }

    public function mapObject($object)
    {
        $class = get_class($object);
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->class_mapper[$class];
    }

    public function validate($object)
    {
        $class = get_class($object);
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"validate_" . sha1($class)}($object);
    }

    public function populate($object, Array $data)
    {
        $class = get_class($object);
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"populate_" . sha1($class)}($object, $data);
    }

    public function trigger($event, $object, Array $args = array())
    {
        $class  = get_class($object);
        $method = "event_{$event}_" . sha1($class);
        if (!is_callable(array($this, $method))) {
            throw new \RuntimeException("Cannot trigger {$event} event on '$class' objects");
        }

        return $this->$method($object, $args);
    }

    public function ensureIndex($db)
    {
        @foreach($indexes as $index)
            $db->{{$index[0]}}->ensureIndex({{var_export($index[1], true)}}, {{var_export($index[2], true)}});
        @end
    }

    @foreach($docs as $doc)
    /**
     *  Populate objects {{$doc['class']}} 
     */
    public function populate_{{sha1($doc['class'])}}(\{{$doc['class']}} $object, Array $data)
    {
        @foreach ($doc['annotation']->getProperties() as $prop)
            @set($name, $prop['property'])
            @if ($prop->has('Id'))
                @set($name, '_id')
            @end
            if (array_key_exists("{{$name}}", $data)) {
            @if (in_array('public', $prop['visibility']))
                $object->{{$prop['property']}} = $data['{{$name}}'];
            @else
                $property = new \ReflectionProperty($object, "{{ $prop['property'] }}");
                $property->setAccessible(true);
                $property->setValue($object, $data['{{$name}}']);
            @end
            }
        @end
    }

    /**
     *  Validate {{$doc['class']}} object
     */
    public function validate_{{sha1($doc['class'])}}(\{{$doc['class']}} $object)
    {
        $doc = array();
        @foreach ($doc['annotation']->getProperties() as $prop)
            /** {{$prop['property']}} */
            @set($propname, $prop['property'])
            @if ($prop->has('Id'))
                @set($propname, '_id')
            @end
            @if (in_array('public', $prop['visibility']))
                if ($object->{{$prop['property']}} !== NULL) {
                    $data = $doc["{{$propname}}"] = $object->{{$prop['property']}};
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
                $data = $doc["{{$propname}}"] = $property->getValue($object);
                @if ($prop->has('Required'))
                    if ($data === NULL) {
                        throw new \RuntimeException("{$prop['property']} cannot be empty");
                    }
                @end
            @end

            @foreach($validators as $name => $callback)
                @if ($prop->has($name))
                    if (empty($this->loaded['{{$files[$name]}}'])) {
                        require_once '{{$files[$name]}}';
                        $this->loaded['{{$files[$name]}}'] = true;
                    }
                    if ($data !== NULL && !{{$callback}}($data)) {
                        throw new \RuntimeException("Validation failed for {{$name}}");
                    }
                @end
            @end
        @end

        return $doc;
    }

        @foreach ($events as $ev)
    /**
     *  Code for {{$ev}} events for objects {{$doc['class']}}
     */
        protected function event_{{$ev}}_{{sha1($doc['class'])}}(\{{$doc['class']}} $document, Array $args)
        {
            @foreach($doc['annotation']->getMethods() as $method)
                @include("trigger", ['method' => $method, 'ev' => $ev, 'doc' => $doc, 'target' => '$document'])
            @end
            @foreach($doc['annotation']->getAll() as $method)
                @if (!empty($plugins[$method['method']]))
                    @set($temp, $plugins[$method['method']])
                    if (empty($this->loaded["{{$temp['file']}}"])) {
                        require_once "{{$temp['file']}}";
                        $this->loaded["{{$temp['file']}}"] = true;
                    }
                    $plugin = new \{{$temp['class']}}({{ var_export($method['args'], true) }});
                    @foreach($temp->getMethods() as $method)
                        @include("trigger", ['method' => $method, 'ev' => $ev, 'doc' => $temp, 'target' => '$plugin'])
                    @end
                @end
            @end
        }
    
        @end

    @end
}
