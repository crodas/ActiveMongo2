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
        spl_autoload_register(array($this, '__autoloader'));
    }

    public function __autoloader($class)
    {
        $class = strtolower($class);
        if (!empty($this->class_mapper[$class])) {
            $this->loaded[$this->class_mapper[$class]['file']] = true;
            require __DIR__ . $this->class_mapper[$class]['file'];

            return true;
        }
        return false;
    }

    public function mapCollection($col)
    {
        if (empty($this->mapper[$col])) {
            throw new \RuntimeException("Cannot map {$col} collection to its class");
        }

        $data = $this->mapper[$col];

        if (empty($this->loaded[$data['file']])) {
            require_once __DIR__ .  $data['file'];
            $this->loaded[$data['file']] = true;
        }

        return $data;
    }

    public function mapClass($class)
    {
        $class = strtolower($class);
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        $data = $this->class_mapper[$class];

        if (empty($this->loaded[$data['file']])) {
            require_once __DIR__ . $data['file'];
            $this->loaded[$data['file']] = true;
        }

        return $data;
    }

    protected function array_unique($array, $toRemove)
    {
        $return = array();
        $count  = array();
        foreach ($array as $key => $value) {
            $val = serialize($value);
            if (empty($count[$val])) {
                $count[$val] = 0;
            }
            $count[$val]++; 
        }
        foreach ($toRemove as $value) {
            $val = serialize($value);
            if (!empty($count[$val]) && $count[$val] != 1) {
                return true;
            }
        }
        return false;
    }

    public function mapObject($object)
    {
        $class = strtolower(get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->class_mapper[$class];
    }

    public function getDocument($object)
    {
        $class = strtolower(get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"get_array_" . sha1($class)}($object);
    }

    public function validate($object)
    {
        $class = strtolower(get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"validate_" . sha1($class)}($object);
    }

    public function update($object, Array $doc, Array $old)
    {
        $class = strtolower(get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"update_" . sha1($class)}($doc, $old);
    }

    public function populate($object, Array $data)
    {
        $class = strtolower(get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"populate_" . sha1($class)}($object, $data);
    }

    public function trigger($event, $object, Array $args = array())
    {
        $class  = strtolower(get_class($object));
        $method = "event_{$event}_" . sha1($class);
        if (!is_callable(array($this, $method))) {
            throw new \RuntimeException("Cannot trigger {$event} event on '$class' objects");
        }

        return $this->$method($object, $args);
    }

    public function updateProperty($document, $key, $value)
    {
        $class  = strtolower(get_class($document));
        $method = "update_property_" . sha1($class);
        if (!is_callable(array($this, $method))) {
            throw new \RuntimeException("Cannot trigger {$event} event on '$class' objects");
        }

        return $this->$method($document, $key, $value);
    }

    public function ensureIndex($db)
    {
        @foreach($indexes as $index)
            $db->{{$index[0]}}->ensureIndex({{var_export($index[1], true)}}, {{var_export($index[2], true)}});
        @end
    }

    @foreach($docs as $doc)
    /**
     *  Get update object {{$doc['class']}} 
     */
    public function update_{{sha1($doc['class'])}}(Array $current, Array $old, $embed = false)
    {
        if (!$embed && $current['_id'] != $old['_id']) {
            throw new \RuntimeException("document ids cannot be updated");
        }

        $change = array();

        @foreach ($doc['annotation']->getProperties() as $prop)
            @set($propname, $prop['property'])
            @set($var, 'current')
            @if ($prop->has('Id'))
                @set($propname, '_id')
            @end

            if (array_key_exists('{{$propname}}', $current)
                || array_key_exists('{{$propname}}', $old)) {

                if (!array_key_exists('{{$propname}}', $current)) {
                    $change['$unset']['{{$propname}}'] = 1;
                } else if (!array_key_exists('{{$propname}}', $old)) {
                    $change['$set']['{{$propname}}'] = $current['{{$propname}}'];
                } else if ($current['{{$propname}}'] !== $old['{{$propname}}']) {
                    @if ($prop->has('Inc'))
                        if (empty($old['{{$propname}}'])) {
                            $prev = 0;
                        } else {
                            $prev = $old['{{$propname}}'];
                        }
                        $change['$inc']['{{$propname}}'] = $current['{{$propname}}'] - $prev;
                    @elif ($prop->has('Embed'))
                        if ($current['{{$propname}}']['__embed_class'] != $old['{{$propname}}']['__embed_class']) {
                            $change['$set']['{{$propname}}.' . $index] = $current['{{$propname}}'];
                        } else {
                            $update = 'update_' . sha1($current['{{$propname}}']['__embed_class']);
                            $diff = $this->$update($current['{{$propname}}'], $old['{{$propname}}'], true);
                            foreach ($diff as $op => $value) {
                                foreach ($value as $p => $val) {
                                    $change[$op]['{{$propname}}.' . $p] = $val;
                                }
                            }
                        }
                    @elif ($prop->has('EmbedMany'))
                        // add things to the array
                        $toRemove = array_diff_key($old['{{$propname}}'], $current['{{$propname}}']);

                        if (count($toRemove) > 0 && $this->array_unique($old['{{$propname}}'], $toRemove)) {
                            $change['$set']['{{$propname}}'] = array_values($current['{{$propname}}']);
                        } else {
                            foreach ($current['{{$propname}}'] as $index => $value) {
                                if (!array_key_exists($index, $old['{{$propname}}'])) {
                                    $change['$push']['{{$propname}}'] = $value;
                                    continue;
                                }
                                if ($value['__embed_class'] != $old['{{$propname}}'][$index]['__embed_class']) {
                                    $change['$set']['{{$propname}}.' . $index] = $value;
                                } else {
                                    $update = 'update_' . sha1($value['__embed_class']);
                                    $diff = $this->$update($value, $old['{{$propname}}'][$index], true);
                                    foreach ($diff as $op => $value) {
                                        foreach ($value as $p => $val) {
                                            $change[$op]['{{$propname}}.' . $index . '.' . $p] = $val;
                                        }
                                    }
                                }
                            }

                            foreach ($toRemove as $value) {
                                $change['$pull']['{{$propname}}'] = $value;
                            }
                        }



                    @elif ($prop->has('ReferenceMany') || $prop->has('Array'))
                        // add things to the array
                        $toRemove = array_diff_key($old['{{$propname}}'], $current['{{$propname}}']);

                        if (count($toRemove) > 0 && $this->array_unique($old['{{$propname}}'], $toRemove)) {
                            $change['$set']['{{$propname}}'] = array_values($current['{{$propname}}']);
                        } else {
                            foreach ($current['{{$propname}}'] as $index => $value) {
                                if (!array_key_exists($index, $old['{{$propname}}'])) {
                                    $change['$push']['{{$propname}}'] = $value;
                                    continue;
                                }
                                if ($old['{{$propname}}'][$index] != $value) {
                                    $change['$set']['{{$propname}}.' . $index] = $value;
                                }
                            }

                            foreach ($toRemove as $value) {
                                $change['$pull']['{{$propname}}'] = $value;
                            }
                        }

                    @else
                        $change['$set']['{{$propname}}'] = $current['{{$propname}}'];
                        @include('validate', compact('propname', 'validators', 'files', 'prop'));
                    @end
                }
            }
        @end

        return $change;
    }

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
                @foreach($hydratations as $zname => $callback)
                    @if ($prop->has($zname))
                        if (empty($this->loaded['{{$files[$zname]}}'])) {
                            require_once __DIR__ .  '{{$files[$zname]}}';
                            $this->loaded['{{$files[$zname]}}'] = true;
                        }
                        
                        {{$callback}}($data['{{$name}}'], {{var_export($prop[0]['args'] ?: [],  true)}}, $this->connection, $this);
                    @end
                @end

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
    public function get_array_{{sha1($doc['class'])}}(\{{$doc['class']}} $object)
    {
        $doc = array();
        @foreach ($doc['annotation']->getProperties() as $prop)
            /* {{$prop['property']}} {{ '{{{' }} */
            @set($propname, $prop['property'])
            @if ($prop->has('Id'))
                @set($propname, '_id')
            @end
            @if (in_array('public', $prop['visibility']))
                if ($object->{{$prop['property']}} !== NULL) {
                    $doc['{{$propname}}'] = $object->{{$prop['property']}};
                }
            @else
                $property = new \ReflectionProperty($object, "{{ $prop['property'] }}");
                $property->setAccessible(true);
                $doc['{{$propname}}'] = $property->getValue($object);
            @end
            /* }}} */
        @end
        return $doc;
    }

    /**
     *  Validate {{$doc['class']}} object
     */
    public function validate_{{sha1($doc['class'])}}(\{{$doc['class']}} $object)
    {
        $doc = $this->get_array_{{sha1($doc['class'])}}($object);

        @foreach ($doc['annotation']->getProperties() as $prop)
            @set($propname, $prop['property'])
            @if ($prop->has('Id'))
                @set($propname, '_id')
            @end
            @if ($prop->has('Required'))
            if (empty($doc['{{$propname}}'])) {
                throw new \RuntimeException("{{$prop['property']}} cannot be empty");
            }
            @end

            @include('validate', compact('propname', 'validators', 'files', 'prop'));
        @end

        return $doc;
    }

    protected function update_property_{{sha1($doc['class'])}}(\{{$doc['class']}} $document, $property, $value)
    {
        @foreach ($doc['annotation']->getProperties() as $prop)
            @set($propname, $prop['property'])
            if ($property ==  '{{$propname}}'
            @foreach($prop->getAll() as $annotation) 
                 || $property == '@{{$annotation['method']}}'
            @end
            ) {
                @if (in_array('public', $prop['visibility']))
                    $document->{{$prop['property']}} = $value;
                @else
                    $property = new \ReflectionProperty($object, "{{ $prop['property'] }}");
                    $property->setAccessible(true);
                    $property->setValue($document, $value);
                @end
            }
        @end
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

            @foreach($doc['annotation']->getAll() as $zmethod)
                @set($first_time, false)
                @if (!empty($plugins[$zmethod['method']]))
                    @set($temp, $plugins[$zmethod['method']])
                    @foreach($temp->getMethods() as $method)
                        @if ($method->has($ev) && empty($first_time)) 
                            if (empty($this->loaded["{{$self->getRelativePath($temp['file'])}}"])) {
                                require_once __DIR__ .  "{{$self->getRelativePath($temp['file'])}}";
                                $this->loaded["{{$self->getRelativePath($temp['file'])}}"] = true;
                            }
                            $plugin = new \{{$temp['class']}}({{ var_export($zmethod['args'], true) }});
                            @set($first_time, true)
                            @include("trigger", ['method' => $method, 'ev' => $ev, 'doc' => $temp, 'target' => '$plugin'])
                        @end
                    @end
                @end
            @end
        }
    
        @end

    @end
}
