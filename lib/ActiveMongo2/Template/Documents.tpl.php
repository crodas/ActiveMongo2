<?php

namespace ActiveMongo2\Generated{{$namespace}};

use ActiveMongo2\Connection;

@set($instance, '_' . uniqid(true))

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

    public function getClass($name)
    {
        $class = __NAMESPACE__ . "\\$name";
        if (!class_exists($class, false)) {
            $define = __NAMESPACE__ . "\\define_class_" . sha1(strtolower($name));
            $define();
        }

        return $class;
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
        if (is_object($class)) {
            $class = $this->get_class($class);
        }

        $class = strtolower($class);
        if (empty($this->class_mapper[$class])) {
            @foreach ($docs as $doc)
                @if (!empty($doc['disc']))
                if ($class == {{@$doc['class']}} ||  $class == {{@$doc['name']}}){
                    return {{@['name' => $doc['name'], 'dynamic' => true, 'prop' => $doc['disc'], 'class' => NULL]}};
                }
                @end
            @end
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
        $class = strtolower($this->get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->class_mapper[$class];
    }

    public function getReference($object, Array $extra = array())
    {
        $class = strtolower($this->get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"get_reference_" . sha1($class)}($object, $extra);
    }

    public function getDocument($object)
    {
        $class = strtolower($this->get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"get_array_" . sha1($class)}($object);
    }

    public function validate($object)
    {
        $class = strtolower($this->get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"validate_" . sha1($class)}($object);
    }

    public function update($object, Array $doc, Array $old)
    {
        $class = strtolower($this->get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"update_" . sha1($class)}($doc, $old);
    }

    public function getRawDocument($object)
    {
        if ($object instanceof ActiveMongo2Mapped){
            return $object->{{$instance}}_getOriginal();
        }

        return array();
    }

    public function populate(&$object, $data)
    {
        $class = strtolower($this->get_class($object));

        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"populate_" . sha1($class)}($object, $data);
    }

    public function trigger($event, $object, Array $args = array())
    {
        if ($object instanceof \ActiveMongo2\Reference) {
            $class = strtolower($object->getClass());
        } else {
            $class = strtolower($this->get_class($object));
        }
        $method = "event_{$event}_" . sha1($class);
        if (!is_callable(array($this, $method))) {
            throw new \RuntimeException("Cannot trigger {$event} event on '$class' objects");
        }

        return $this->$method($object, $args);
    }

    public function getMapping($class)
    {
        if (is_object($class)) {
            $class = $this->get_class($class);
        }
        $func  = "get_mapping_" . sha1($class);
        if (!is_callable(array($this, $func))) {
            throw new \Exception("Cannot map $class");
        }
        return $this->$func();
    }

    public function getObjectClass($col, $array)
    {
        if ($array instanceof \MongoGridFsFile) {
            $array = $array->file;
        }
        if ($col instanceof \MongoCollection) {
            $col = $col->getName();
        }
        $class = NULL;
        switch ($col) {
        @foreach ($docs as $doc)
            @if ($doc['is_gridfs'])
            case {{@$doc['name'] . '.files'}}:
            case {{@$doc['name'] . '.chunks'}}:
            @else
            case {{@$doc['name']}}:
            @end
                @if (empty($doc['disc']))
                    $class = {{@$doc['class']}};
                @else
                    if (!empty($array[{{@$doc['disc']}}])) {
                        $class = $array[{{@$doc['disc']}}];
                    }
                @end
                break;
        @end
        }

        if (empty($class)) {
            throw new \RuntimeException("Cannot get class for collection {$col}");
        }


        return $this->getClass($this->class_mapper[$class]['name'] . '_' . sha1($class));

        return $class;
    }

    public function get_class($object)
    {
        if ($object instanceof ActiveMongo2Mapped) {
            $class = $object->{{$instance}}_getClass();
        } else if ($object instanceof \ActiveMongo2\Reference) {
            $class = $object->getClass();
        } else {
            $class = strtolower(get_class($object));
        }

        return $class;
    }

    public function updateProperty($document, $key, $value)
    {
        $class  = strtolower($this->get_class($document));
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
    protected function update_{{sha1($doc['class'])}}(Array $current, Array $old, $embed = false)
    {
        if (!$embed && !empty($current['_id']) && $current['_id'] != $old['_id']) {
            throw new \RuntimeException("document ids cannot be updated");
        }

        @if (empty($doc['parent']))
            $change = array();
        @else
            $change = $this->update_{{sha1($doc['parent'])}}($current, $old, $embed);
        @end

        @foreach ($doc['annotation']->getProperties() as $prop)
            @set($docname, $prop['property'])
            @set($propname, $prop['property'])
            @set($current, "current")
            @if ($prop->has('Id'))
                @set($propname, '_id')
            @end

            @if ($doc['is_gridfs'])
                // GridFS collection detected! it is special :-)
                @set($current, "current['metadata']")
                @set($docname, "metadata." . $propname)
            @end

            if (array_key_exists({{@$propname}}, ${{$current}})
                || array_key_exists({{@$propname}}, $old)) {

                if (!array_key_exists({{@$propname}}, ${{$current}})) {
                    $change['$unset'][{{@$docname}}] = 1;
                } else if (!array_key_exists({{@$propname}}, $old)) {
                    $change['$set'][{{@$docname}}] = ${{$current}}[{{@$propname}}];
                } else if (${{$current}}[{{@$propname}}] !== $old[{{@$propname}}]) {
                    @if ($prop->has('Inc'))
                        if (empty($old[{{@$propname}}])) {
                            $prev = 0;
                        } else {
                            $prev = $old[{{@$propname}}];
                        }
                        $change['$inc'][{{@$docname}}] = ${{$current}}[{{@$propname}}] - $prev;
                    @elif ($prop->has('Embed'))
                        if (${{$current}}[{{@$propname}}]['__embed_class'] != $old[{{@$propname}}]['__embed_class']) {
                            $change['$set'][{{@$docname.'.'}} . $index] = ${{$current}}[{{@$propname}}];
                        } else {
                            $update = 'update_' . sha1(${{$current}}[{{@$propname}}]['__embed_class']);
                            $diff = $this->$update(${{$current}}[{{@$propname}}], $old[{{@$propname}}], true);
                            foreach ($diff as $op => $value) {
                                foreach ($value as $p => $val) {
                                    $change[$op][{{@$docname.'.'}} . $p] = $val;
                                }
                            }
                        }
                    @elif ($prop->has('EmbedMany'))
                        // add things to the array
                        $toRemove = array_diff_key($old[{{@$propname}}], ${{$current}}[{{@$propname}}]);

                        if (count($toRemove) > 0 && $this->array_unique($old[{{@$propname}}], $toRemove)) {
                            $change['$set'][{{@$docname}}] = array_values(${{$current}}[{{@$propname}}]);
                        } else {
                            foreach (${{$current}}[{{@$propname}}] as $index => $value) {
                                if (!array_key_exists($index, $old[{{@$propname}}])) {
                                    $change['$push'][{{@$docname}}] = $value;
                                    continue;
                                }
                                if ($value['__embed_class'] != $old[{{@$propname}}][$index]['__embed_class']) {
                                    $change['$set'][{{@$docname.'.'}} . $index] = $value;
                                } else {
                                    $update = 'update_' . sha1($value['__embed_class']);
                                    $diff = $this->$update($value, $old[{{@$propname}}][$index], true);
                                    foreach ($diff as $op => $value) {
                                        foreach ($value as $p => $val) {
                                            $change[$op][{{@$docname.'.'}} . $index . '.' . $p] = $val;
                                        }
                                    }
                                }
                            }

                            foreach ($toRemove as $value) {
                                if (!empty($value['__instance'])) {
                                    $change['$pull'][{{@$docname}}]['__instance']['$in'][] = $value['__instance'];
                                } else {
                                    $change['$pull'][{{@$docname}}][] = $value;
                                }
                            }
                        }



                    @elif ($prop->has('ReferenceMany') || $prop->has('Array'))
                        // add things to the array
                        $toRemove = array_diff_key($old[{{@$propname}}], ${{$current}}[{{@$propname}}]);

                        if (count($toRemove) > 0 && $this->array_unique($old[{{@$propname}}], $toRemove)) {
                            $change['$set'][{{@$docname}}] = array_values(${{$current}}[@{{$propname}}]);
                        } else {
                            foreach (${{$current}}[{{@$propname}}] as $index => $value) {
                                if (!array_key_exists($index, $old[{{@$propname}}])) {
                                    $change['$push'][{{@$docname}}] = $value;
                                    continue;
                                }
                                if ($old[{{@$propname}}][$index] != $value) {
                                    $change['$set'][{{@$docname . '.'}} . $index] = $value;
                                }
                            }

                            foreach ($toRemove as $value) {
                                if (!empty($value['__instance'])) {
                                    $change['$pull'][{{@$docname}}]['__instance']['$in'][] = $value['__instance'];
                                } else {
                                    $change['$pull'][{{@$docname}}] = $value;
                                }
                            }
                        }

                    @else
                        $change['$set'][{{@$docname}}] = ${{$current}}[{{@$propname}}];
                    @end
                }
            }
        @end

        return $change;
    }

    protected function get_mapping_{{sha1($doc['class'])}}() 
    {
        return array(
            @foreach ($doc['annotation']->getProperties() as $prop)
                @set($cname, $prop['property'])
                @set($pname, $cname);
                @if ($prop->has('Id'))
                    @set($cname, '_id')
                @elif ($doc['is_gridfs']) 
                    @set($pname, 'metadata.' . $pname)
                @end
                {{@$pname}} => {{@$cname}},
            @end
        );
    }

    /**
     *  Populate objects {{$doc['class']}} 
     */
    protected function populate_{{sha1($doc['class'])}}(\{{$doc['class']}} &$object, $data)
    {
        if (!$object instanceof ActiveMongo2Mapped) {
            $class    = $this->getClass({{@$doc['name'] . '_' }} .  sha1(strtolower(get_class($object))));
            $populate = get_object_vars($object);
            $object = new $class;
            foreach ($populate as $key => $value) {
                $object->$key = $value;
            }
        }

        @if (!empty($doc['parent']))
            $this->populate_{{sha1($doc['parent'])}}($object, $data);
        @end

        @if ($doc['is_gridfs'])
            if (!$data instanceof \MongoGridFsFile) {
                throw new \RuntimeException("Internal error, trying to populate a GridFSFile with an array");
            }
            $data_file = $data;
            $data      = $data->file;
            if (empty($data['metadata'])) {
                $data['metadata'] = [];
            }
        @else

            if (!is_array($data)) {
                throw new \RuntimeException("Internal error, trying to populate a document with a wrong data");
            }
        @end

        $object->{{$instance}}_setOriginal($data);

        @foreach ($doc['annotation']->getProperties() as $prop)
            @set($docname,  $prop['property'])
            @set($propname, $prop['property'])
            @set($data, '$data')

            @if ($prop->has('Id'))
                @set($docname, '_id')
            @elif ($doc['is_gridfs'])
                @set($data, '$data["metadata"]')
            @end
            @if ($prop->has('Stream'))
                @if (in_array('public', $prop['visibility']))
                    $object->{{$prop['property']}} = $data_file->getResource();
                @else
                    $property = new \ReflectionProperty($object, {{@$prop['property']}});
                    $property->setAccessible(true);
                    $property->setValue($object, $data_file->getResource());
                @end
                @continue
            @end
            if (array_key_exists("{{$docname}}", {{$data}})) {
                @foreach($hydratations as $zname => $callback)
                    @if ($prop->has($zname))
                        if (empty($this->loaded[{{@$files[$zname]}}])) {
                            require_once __DIR__ .  {{@$files[$zname]}};
                            $this->loaded[{{@$files[$zname]}}] = true;
                        }
                        
                        {{$callback}}({{$data}}[{{@$docname}}], {{var_export($prop[0]['args'] ?: [],  true)}}, $this->connection, $this);
                    @end
                @end

                @if (in_array('public', $prop['visibility']))
                    $object->{{$prop['property']}} = {{$data}}[{{@$docname}}];
                @else
                    $property = new \ReflectionProperty($object, {{@$prop['property']}});
                    $property->setAccessible(true);
                    $property->setValue($object, {{$data}}[{{@$docname}}]);
                @end
                
            }
        @end
    }

    /**
     *  Get reference of  {{$doc['class']}} object
     */
    protected function get_reference_{{sha1($doc['class'])}}(\{{$doc['class']}} $object, $include = Array())
    {
        $document = $this->get_array_{{sha1($doc['class'])}}($object);
        $extra    = array();
        if ($include) {
            $extra  = array_intersect_key($document, $include);
        }

        @if (!empty($refCache[$doc['class']]))
            $extra = array_merge($extra,  array_intersect_key(
                $document, 
                {{@array_combine($refCache[$doc['class']], $refCache[$doc['class']])}}
            ));
        @end
        
        foreach ($extra as $key => $value) {
            if (is_object($value)) {
                if ($value instanceof \ActiveMongo2\Reference) {
                    $extra[$key] = $value->getReference();
                } else {
                    $extra[$key] = $this->getReference($value);
                }
            }
        }

        return array_merge(array(
                '$id'   => $document['_id'],
                '$ref'  => {{@$doc['name']}}, 
                '__class' => {{@$doc['class']}},
            )
            , $extra
        );

    }

    /**
     *  Validate {{$doc['class']}} object
     */
    protected function get_array_{{sha1($doc['class'])}}(\{{$doc['class']}} $object, $recursive = true)
    {
        @if (empty($doc['parent']))
            $doc = array();
        @else
            $doc = $recursive ? $this->get_array_{{sha1($doc['parent'])}}($object) : array();
        @end

        @set($docz, '$doc')
        @if ($doc['is_gridfs'])
            @set($docz, '$doc["metadata"]')
        @end


        @foreach ($doc['annotation']->getProperties() as $prop)
            /* {{$prop['property']}} */
            @set($propname, $prop['property'])
            @set($docname, $propname)
            @if ($prop->has('Id'))
                @set($docz, '$doc')
                @set($docname, '_id')
            @end
            @if (in_array('public', $prop['visibility']))
                if ($object->{{$propname}} !== NULL) {
                    {{$docz}}[{{@$docname}}] = $object->{{$propname}};
                }
            @else
                $property = new \ReflectionProperty($object, {{ @$propname }});
                $property->setAccessible(true);
                {{$docz}}[{{@$docname}}] = $property->getValue($object);
            @end
            @if ($doc['is_gridfs'])
                @set($docz, '$doc["metadata"]')
            @end
        @end

        @foreach ($doc['annotation']->getProperties() as $prop)
            @set($propname, $prop['property'])
            @if ($prop->has('Id'))
                @set($propname, '_id')
            @end
            @foreach ($defaults as $name => $callback) 
                @if ($prop->has($name))
                    // default: {{$name}}
                    if (empty({{$docz}}[{{@$propname}}])) {
                        if (empty($this->loaded[{{@$files[$name]}}])) {
                            require_once __DIR__ . {{@$files[$name]}};
                            $this->loaded[{{@$files[$name]}}] = true;
                        }
                        {{$docz}}[{{@$propname}}] = {{$callback}}({{$docz}}, {{@$prop->getOne($name)}}, $this->connection, $this); 
                    }
                @end
            @end
        @end

        @if (!empty($doc['disc']))
            {{$docz}}[{{@$doc['disc']}}] = {{@$doc['class']}};
        @end

        return $doc;
    }

    /**
     *  Validate {{$doc['class']}} object
     */
    protected function validate_{{sha1($doc['class'])}}(\{{$doc['class']}} $object)
    {
        @if (!empty($doc['parent']))
            $doc = array_merge(
                $this->validate_{{sha1($doc['parent'])}}($object),
                $this->get_array_{{sha1($doc['class'])}}($object, false)
            );
        @else 
            $doc = $this->get_array_{{sha1($doc['class'])}}($object);
        @end

        @set($docz, '$doc')
        @if ($doc['is_gridfs'])
            @set($docz, '$doc["metadata"]')
        @end
        @foreach ($doc['annotation']->getProperties() as $prop)
            @set($propname, $prop['property'])
            @if ($prop->has('Id'))
                @set($propname, '_id')
            @end
            @if ($prop->has('Required'))
            if (empty({{$docz}}[{{@$propname}}])) {
                throw new \RuntimeException("{{$prop['property']}} cannot be empty");
            }
            @end

            @include('validate', compact('propname', 'validators', 'files', 'prop'));
        @end

        return $doc;
    }

    protected function update_property_{{sha1($doc['class'])}}(\{{$doc['class']}} $document, $property, $value)
    {
        @if ($doc['parent'])
            $this->update_property_{{sha1($doc['parent'])}}($document, $property, $value);
        @end
        @foreach ($doc['annotation']->getProperties() as $prop)
            @set($propname, $prop['property'])
            if ($property ==  {{@$propname}}
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
        protected function event_{{$ev}}_{{sha1($doc['class'])}}($document, Array $args)
        {
            $class = $this->get_class($document);
            if ($class != {{@$doc['class']}} && !is_subclass_of($class, {{@$doc['class']}})) {
                throw new \Exception("Class invalid class name ($class) expecting  "  . {{@$doc['class']}});
            }
            @if (!empty($doc['parent']))
                $this->event_{{$ev}}_{{sha1($doc['parent'])}}($document, $args);
            @end

            @foreach($doc['annotation']->getMethods() as $method)
                @include("trigger", ['method' => $method, 'ev' => $ev, 'doc' => $doc, 'target' => '$document'])
            @end

            @if ($ev =="postCreate" || $ev == "postUpdate")
                $col = $args[1]->getDatabase()->references_queue;
                @foreach ($references as $col => $refs)
                    @foreach ($refs as $ref)
                        @if ($ref['class'] == $doc['class'] && $ref['deferred'])
                            @if ($ev == "postCreate")
                            if (!empty($args[0][{{@$ref['property']}}])) {
                            @else
                            if (!empty($args[0]['$set'][{{@$ref['property']}}])) {
                            @end
                                /* Keep in track of the reference */
                                @if ($ref['multi'])
                                    $data = [];
                                    @if ($ev == "postCreate")
                                    foreach ($args[0][{{@$ref['property']}}] as $id => $row) {
                                    @else
                                    foreach ($args[0]['$set'][{{@$ref['property']}}] as $id => $row) {
                                    @end
                                        $data[] = [
                                            @if ($ev == "postCreate")
                                            'source_id'     => {{@$ref['target'] . '::'}} . serialize($row['$id']),
                                            'id'            => $args[0]['_id'],
                                            @else
                                            'source_id'     => {{@$ref['target'] . '::'}} . serialize($row['$id']),
                                            'id'            => $args[2],
                                            @end
                                            'property'      => {{@$ref['property'] . '.'}} . $id,
                                        ];
                                    }
                                @else
                                    $data = [[
                                        @if ($ev == "postCreate")
                                        'source_id'     => {{@$ref['target'] . '::'}} . serialize($args[0][{{@$ref['property']}}]['$id']),
                                        'id'            => $args[0]['_id'],
                                        @else
                                        'source_id'     => {{@$ref['target'] . '::'}} . serialize($args[0]['$set'][{{@$ref['property']}}]['$id']),
                                        'id'            => $args[2],
                                        @end
                                        'property'      => {{@$ref['property']}},
                                ]];
                                @end
                                foreach ($data as $row) {
                                    $row['collection'] = {{@$ref['collection']}};
                                    $row['_id'] = array(
                                        'source' => $row['source_id'], 
                                        'target_id' => $row['id'], 
                                        'target_col' => $row['collection'], 
                                        'target_prop' => $row['property']
                                    );
                                    $col->save($row, array('w' => 1));
                                }
                            }
                        @end
                    @end
                @end
            @end

            @if ($ev == "postUpdate" && !empty($references[$doc['class']]))
                @include('reference/update.tpl.php', compact('doc', 'references'))
            @end

            @foreach($doc['annotation']->getAll() as $zmethod)
                @set($first_time, false)
                @if (!empty($plugins[$zmethod['method']]))
                    @set($temp, $plugins[$zmethod['method']])
                    @foreach($temp->getMethods() as $method)
                        @if ($method->has($ev) && empty($first_time)) 
                            if (empty($this->loaded[{{@$self->getRelativePath($temp['file'])}}])) {
                                require_once __DIR__ .  {{@$self->getRelativePath($temp['file'])}};
                                $this->loaded[{{@$self->getRelativePath($temp['file'])}}] = true;
                            }
                            @if (!in_array('static', $temp['visibility']))
                                // {{$method[0]['method']}}
                                $plugin = new \{{$temp['class']}}({{ var_export($zmethod['args'], true) }});
                                @set($first_time, true)
                            @end
                            @include("trigger", ['method' => $method, 'ev' => $ev, 'doc' => $temp, 'target' => '$plugin', 'args' => $zmethod['args']])
                        @end
                    @end
                @end
            @end
        }
    
        @end

    @end
}

interface ActiveMongo2Mapped
{
    public function {{$instance}}_getClass();
    public function {{$instance}}_setOriginal(Array $data);
    public function {{$instance}}_getOriginal();
}

@foreach ($docs as $doc) 
    @set($name, strtolower($doc['name']) . '_' . sha1($doc['class']))

/**
 * 
 */
function define_class_{{sha1($name)}}()
{

    if (!class_exists({{@"\\".$doc['class']}}, false)) {
        require_once __DIR__ . {{@$doc['file']}};
    }

    final class {{$name}} extends \{{$doc['class']}} implements ActiveMongo2Mapped
    {
        private ${{$instance}}_original;

        public function {{$instance}}_getClass()
        {
            return {{@$doc['class']}};
        }

        public function {{$instance}}_setOriginal(Array $data)
        {
            $this->{{$instance}}_original = $data;
        }

        public function {{$instance}}_getOriginal()
        {
            return $this->{{$instance}}_original;
        }

        public function __destruct()
        {
            if(is_callable('parent::__destruct')) {
                parent::__destruct();
            }
        }
    }
}
@end
