<?php

namespace ActiveMongo2\Generated{{$namespace}};

use ActiveMongo2\Connection;

@set($instance, '_' . uniqid(true))

class Mapper
{
    protected $mapper = {{ var_export($collections->byName(), true) }};
    protected $class_mapper = {{ var_export($collections->byClass(), true) }};
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
            $define = __NAMESPACE__ . "\\define_class_" . sha1($name);
            $define();
        }

        return $class;
    }

    protected function array_diff(Array $arr1, Array $arr2)
    {
        $diff = array();
        foreach ($arr1 as $key => $value) {
            if (empty($arr2[$key]) || $arr2[$key] !== $arr1[$key]) {
                $diff[$key] = $value;
            }
        }
        return $diff;
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

    public function onQuery($table, Array &$query)
    {
        switch ($table) {
        @foreach($collections as $collection)
        case {{@$collection->getClass()}}:
            @if ($collection->isSingleCollection()) {
                $query[{{@$collection->getDiscriminator()}}] = $table;
            @end
            break;
        @end
        }
    }

    public function mapClass($class)
    {
        if (is_object($class)) {
            $class = $this->get_class($class);
        }

        $class = strtolower($class);
        if (empty($this->class_mapper[$class])) {
            @foreach($collections as $collection)
                @if ($collection->isSingleCollection())
                if ($class == {{@$collection->getClass()}} ||  $class == {{@$collection->getName()}}){
                    return {{@['name' => $collection->getName(), 'dynamic' => true, 'prop' => $collection->getDiscriminator(), 'class' => NULL]}};
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

    public function update($object, Array &$doc, Array $old)
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

    public function getObjectClass($col, $doc)
    {
        if ($doc instanceof \MongoGridFsFile) {
            $doc = $doc->file;
        }
        if ($col instanceof \MongoCollection) {
            $col = $col->getName();
        }
        $class = NULL;
        switch ($col) {
        @foreach ($collections as $collection)
            @if ($collection->isGridFS())
            case {{@$collection->getName() . '.files'}}:
            case {{@$collection->getName() . '.chunks'}}:
            @else
            case {{@$collection->getName()}}:
            @end
                @if (!$collection->isSingleCollection())
                    $class = {{@$collection->getClass()}};
                @else
                    if (!empty({{$collection->getDiscriminator(true)->getPHPVariable()}})) {
                        $class = {{ $collection->getDiscriminator(true)->getPHPVariable()}};
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
        @set($collection, $collections[$doc['class']])

    /**
     *  Get update object {{$collection->getClass()}} 
     */
    protected function update_{{sha1($collection->getClass())}}(Array &$current, Array $old, $embed = false)
    {
        if (!$embed && !empty($current['_id']) && $current['_id'] != $old['_id']) {
            throw new \RuntimeException("document ids cannot be updated");
        }

        @if (!$collection->getParent()) {
            $change = array();
        @else
            $change = $this->update_{{sha1($collection->getParent())}}($current, $old, $embed);
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
                                    @if ($prop->has('ReferenceMany'))
                                        $change['$addToSet'][{{@$docname}}]['$each'][] = $value;
                                    @else
                                        $change['$push'][{{@$docname}}] = $value;
                                    @end
                                    continue;
                                }

                                if (!empty($old[{{@$propname}}][$index]['__instance']) && is_array($value)) {
                                    // __instance is an internal variable that helps
                                    // activemongo2 to remove sub objects from arrays easily.
                                    // Its value is private to the library and it shouldn't change
                                    // unless the value of the object changes
                                    $diff = $this->array_diff(
                                        $value,
                                        $old[{{@$propname}}][$index]
                                    );
                                    if (count($diff) == 1 && !empty($diff['__instance'])) {
                                        $value['__instance'] = $old[{{@$propname}}][$index]['__instance'];
                                        ${{$current}}[{{@$propname}}][$index] = $value;
                                    }
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

    protected function get_mapping_{{sha1($collection->getClass())}}() 
    {
        return array(
            @foreach ($collection->getProperties() as $prop)
                {{@$prop->getName(true)}} => {{@$prop->getProperty()}},
            @end
        );
    }

    /**
     *  Populate objects {{$collection->getClass()}} 
     */
    protected function populate_{{sha1($collection->getClass())}}(\{{$collection->getClass()}} &$object, $data)
    {
        if (!$object instanceof ActiveMongo2Mapped) {
            $class    = $this->getClass({{@$collection->getName() . '_' }} .  sha1(strtolower(get_class($object))));
            $populate = get_object_vars($object);
            $object = new $class;
            foreach ($populate as $key => $value) {
                $object->$key = $value;
            }
        }

        @if ($p = $collection->getParent())
            $this->populate_{{sha1($p->getClass())}}($object, $data);
        @end

        @if ($collection->isGridFs())
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

        $zData = $data;

        @foreach ($doc['annotation']->getProperties() as $prop)
            @set($docname,  $prop['property'])
            @set($propname, $prop['property'])
            @set($data, '$data')

            @if ($prop->has('Id'))
                @set($docname, '_id')
            @elif ($doc['is_gridfs'])
                @set($data, '$data["metadata"]')
            @end

                
            @if ($prop->has('ReferenceMany'))
                if (!empty($zData[{{@$docname}}])) {
                    foreach($zData[{{@$docname}}] as $id => $sub) {
                        if (empty($sub['__instance']) || !strpos($sub['__instance'], $sub['$ref'])) {
                            $sub['__instance'] = $sub['$ref'] . ':' . serialize($sub['$id']) ;
                        }
                        $zData[{{@$docname}}][$id] = $sub;
                        $data[{{@$docname}}][$id]  = $sub;
                    }
                }
            @elif ($prop->has('Stream'))
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

        $object->{{$instance}}_setOriginal($zData);


    }

    /**
     *  Get reference of  {{$collection->getClass()}} object
     */
    protected function get_reference_{{sha1($collection->getClass())}}(\{{$collection->getClass()}} $object, $include = Array())
    {
        $document = $this->get_array_{{sha1($collection->getClass())}}($object);
        $extra    = array();
        if ($include) {
            $extra  = array_intersect_key($document, $include);
        }

        @if ($cache = $collection->getRefCache())
            $extra = array_merge($extra,  array_intersect_key(
                $document, 
                {{@$cache}}
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
                '$ref'  => {{@$collection->getName()}}, 
                '__class' => {{@$collection->getClass()}},
                '__instance' => {{@$collection->getName()}} . ':' . serialize($document['_id']),
            )
            , $extra
        );

    }

    /**
     *  Validate {{$collection->getClass()}} object
     */
    protected function get_array_{{sha1($collection)}}(\{{$collection}} $object, $recursive = true)
    {
        @if (!$collection->getParent())
            $doc = array();
        @else
            $doc = $recursive ? $this->get_array_{{sha1($collection->getParent())}}($object) : array();
        @end

        @foreach ($collection->getProperties() as $prop)
            @if ($prop->isPublic())
                /* Public property {{$prop->getPHPName()}} -> {{$prop->getName()}} */
                if ($object->{{$prop->getPHPName()}} !== NULL) {
                    {{ $prop->getPHPVariable()}} = $object->{{ $prop->getPHPName() }};
                }
            @else
                $property = new \ReflectionProperty($object, {{ @$prop->getPHPName() }});
                $property->setAccessible(true);
                {{$prop->getPHPVariable()}} = $property->getValue($object);
            @end
        @end

        @foreach ($collection->getProperties() as $prop)
            @foreach($prop->getDefault() as $default)
                if (empty({{$prop->getPHPVariable()}})) {
                    {{$default->toCode($prop)}}
                    {{$prop->getPHPVariable()}} = $return;
                }
            @end
        @end

        @if ($collection->isSingleCollection())
            // SINGLE COLLECTION
            {{$collection->getDiscriminator(true)->getPHPVariable()}} = {{@$collection->getClass()}};
        @end 

        return $doc;
    }

    /**
     *  Validate {{$collection->getClass()}} object
     */
    protected function validate_{{sha1($collection->getClass())}}(\{{$collection->getClass()}} $object)
    {
        @if ($collection->getParent())
            $doc = array_merge(
                $this->validate_{{sha1($collection->getParent())}}($object),
                $this->get_array_{{sha1($collection->getClass())}}($object, false)
            );
        @else 
            $doc = $this->get_array_{{sha1($collection->getClass())}}($object);
        @end

        @set($docz, '$doc')
        @if ($collection->isGridFS())
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

            @include('validate', compact('propname', 'validators', 'files', 'prop', 'collection'));
        @end

        return $doc;
    }

    protected function update_property_{{sha1($collection->getClass())}}(\{{$collection->getClass()}} $document, $property, $value)
    {
        @if ($collection->getParent())
            $this->update_property_{{sha1($collection->getParent())}}($document, $property, $value);
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


        @foreach ($collections->getEvents() as $ev)
    /**
     *  Code for {{$ev}} events for objects {{$collection->getClass()}}
     */
        protected function event_{{$ev}}_{{sha1($collection->getClass())}}($document, Array $args)
        {
            $class = $this->get_class($document);
            if ($class != {{@$collection->getClass()}} && !is_subclass_of($class, {{@$collection->getClass()}})) {
                throw new \Exception("Class invalid class name ($class) expecting  "  . {{@$collection->getClass()}});
            }
            @if ($collection->getParent())
                $this->event_{{$ev}}_{{sha1($collection->getParent()->getClass())}}($document, $args);
            @end

            @foreach ($collection->getMethodsByAnnotation($ev) as $method)
                {{$method->toCode($collection, '$document')}}
                if ($return === FALSE) {
                    throw new \RuntimeException;
                }
            @end

            @if ($ev =="postCreate" || $ev == "postUpdate")
                $col = $args[1]->getDatabase()->references_queue;
                @include("reference/deferred.tpl.php", compact('ev', 'collection'))
                @if ($ev == "postUpdate")
                    @include("reference/update.tpl.php", compact('ev', 'collection'))
                @end
            @end

            @foreach ($collection->getPlugins($ev) as $plugin)
                {{$plugin->toCode($collection, '$document')}}
                if ($return === FALSE) {
                    throw new \RuntimeException;
                }
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

@foreach ($collections as $collection)
/**
 * 
 */
function define_class_{{sha1($collection->getHash())}}()
{

    if (!class_exists({{@"\\".$collection->getClass()}}, false)) {
        require_once __DIR__ . {{@$collection->getPath()}};
    }

    final class {{$collection->getHash()}} extends \{{$collection->getClass()}} implements ActiveMongo2Mapped
    {
        private ${{$instance}}_original;

        public function {{$instance}}_getClass()
        {
            return {{@$collection->getClass()}};
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
