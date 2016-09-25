<?php

namespace {{trim($namespace, '\\')}};

use MongoClient;
use ActiveMongo2\Connection;
use Notoj\Annotation\Annotation;
use Notoj\Annotation\Annotations;
use Notoj;

@foreach ($collections->byName() as $name => $class)
    define({{@'C' . $name}}, {{@$class['class']}});
@end

@set($instance, '_' . uniqid(true))

class Mapper
{
    protected $mapper = {{ var_export(@$collections->byName(), true) }};
    protected $class_mapper = {{ var_export(@$collections->byClass(), true) }};
    protected $class_files =  {{ @$collections->autoload() }};
    protected static $loaded = array();
    protected $connection;
    protected $connections;
    protected $class_connections = {{@$collections->byConnection()}};
    protected $ns = array();
    protected $ns_by_name = array();

    public function __construct(Connection $conn)
    {
        $this->connection = $conn;
        spl_autoload_register(array($this, '__autoloader'));
    }

    public function setDatabases(Array $conns, Array $ns)
    {
        $this->connections = $conns;

        $this->ns = $ns;
        foreach ($this->class_connections as $class => $conn) {
            $ns ="";
            if (!empty($this->ns[$conn])) {
                $ns = $this->ns[$conn];
            }
            if (!empty($this->class_mapper[$class])) {
                $this->ns_by_name[ $this->class_mapper[$class]['name'] ] = $ns;
            }
        }

        return $this;
    }

    public function getRelativePath($object, $dir)
    {
        if ($dir[0] == '/') {
            return $dir;
        }
        $info = $this->mapClass($object);
        return $info['dir'] . "/" . $dir;
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

    public function getCollections()
    {
        return array(
        @foreach ($collections as $collection)
            @if ($collection->getName())
                {{$collection->getClassCode()}} => $this->ns_by_name[{{@$collection->getName()}}]. {{@$collection->getName()}},
            @end
        @end
        );
    }

    public function __autoloader($class)
    {
        $class = strtolower($class);
        if (!empty($this->class_files[$class])) {
            self::$loaded[$this->class_files[$class]] = true;
            require $this->class_files[$class];

            return true;
        }
        return false;
    }

    public function getCollectionObject($col)
    {
        if (!is_scalar($col) || empty($this->mapper[$col])) {
            $data = $this->mapClass($col);     
        } else {
            $data = $this->mapper[$col];
        }

        if (empty(self::$loaded[$data['file']])) {
            if (!$data['verify']($data['class'], false)) {
                require $data['file'];
            }
            self::$loaded[$data['file']] = true;
        }

        $data['name'] = $this->ns_by_name[$data['name']] . $data['name'];

        $conn = $this->class_connections[$data['class']];

        if (empty($this->connections[$conn])) {
            throw new \RuntimeException("Cannot find connection $conn. We have " . print_r(array_keys($this->connections), true));
        }

        $db = $this->connections[$conn];
        if (!empty($data['is_gridfs'])) {
            $col = $db->getGridFs($data['name']);
        } else {
            $col = $db->selectCollection($data['name']);
        }

        return [$col, $data['class']];
    }

    public function mapCollection($col)
    {
        $col = strtolower($col);
        if (empty($this->mapper[$col])) {
            throw new \RuntimeException("Cannot map {$col} collection to its class");
        }

        $data = $this->mapper[$col];

        if (empty(self::$loaded[$data['file']])) {
            if (!$data['verify']($data['class'], false)) {
                require $data['file'];
            }
            self::$loaded[$data['file']] = true;
        }

        return $data;
    }

    public function onQuery($table, &$query)
    {
        if (!is_array($query)) {
            if ($query instanceof \MongoId) {
                $query = ['_id' => $query];
            } else if (is_scalar($query)) {
                if (is_numeric($query)) {
                    $query = ['_id' => [
                        '$in' => [$query . '', 0+$query],
                    ]];
                } else if (preg_match('/^[0-9a-f]{24}$/i', $query)) {
                    $query = ['_id' => [
                        '$in' => [$query, new \MongoId($query)],
                    ]];
                } else {
                    $query = ['_id' => $query];
                }
            }
        }

        switch ($table) {
        @foreach($collections as $collection)
            case {{$collection->getClassCode()}}:
                @if ($collection->is('SingleCollection') && $collection->getParent()) {
                    $query[{{@$collection->getDiscriminator()}}] = {{$collection->getClassCode()}};
                @end
                @foreach ($collection->getMethodsByAnnotation('onQuery') as $method)
                    {{$method->toCode($collection, '$query')}}
                @end
                @foreach ($collection->getPlugins('onQuery') as $plugin)
                    {{$plugin->toCode($collection, '$query')}}
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
                @if ($collection->is('SingleCollection'))
                if ($class == {{$collection->getClassCode()}} ||  $class == {{@$collection->getName()}}){
                    return {{@['name' => $collection->getName(), 'dynamic' => true, 'prop' => $collection->getDiscriminator(), 'class' => NULL]}};
                }
                @end
            @end
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        $data = $this->class_mapper[$class];

        if (empty(self::$loaded[$data['file']])) {
            if (!$data['verify']($data['class'], false)) {
                require $data['file'];
            }
            self::$loaded[$data['file']] = true;
        }

        return $data;
    }

    protected function is_array($array)
    {
        if (is_array($array)) {
            $keys = array_keys($array);
            $expected = range(0, count($array)-1);
            return count(array_diff($keys, $expected)) == 0;
        }
        return false;
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

    public function getReflection($name)
    {
        $class = strtolower($name);
        if (empty($this->class_mapper[$class])) {
            if (empty($this->mapper[$name])) {
                throw new \RuntimeException("Cannot map class {$class} to its document");
            }
            $class = $this->mapper[$name]['class'];
        }

        return new \ActiveMongo2\Reflection\Collection($this->{"reflect_" . sha1($class)}(), $this);
    }

    public function getReference($object, Array $extra = array())
    {
        $class = strtolower($this->get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }
        return $this->{"get_reference_" . sha1($class)}($object, $extra);
    }

    public function populateFromArray($object, Array $data)
    {
        $class = strtolower($this->get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"populate_from_array_" . sha1($class)}($object, $data);
    }


    public function getDocument($object)
    {
        if ($object instanceof \ActiveMongo2\Reference) {
            $object = $object->getObject();
        }
        $class = strtolower($this->get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"get_array_" . sha1($class)}($object);
    }

    public function validate($object)
    {
        $old   = $this->getRawDocument($object);
        $class = strtolower($this->get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"validate_" . sha1($class)}($object, $old);
    }

    public function set_property($object, $name, $value)
    {
        $class = strtolower($this->get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"set_property_" . sha1($class)}($object, $name, $value);
    }

    public function get_property($object, $name)
    {
        $class = strtolower($this->get_class($object));
        if (empty($this->class_mapper[$class])) {
            throw new \RuntimeException("Cannot map class {$class} to its document");
        }

        return $this->{"get_property_" . sha1($class)}($object, $name);
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
        if (!empty($object->{{$instance}}) && $object->{{$instance}} instanceof ActiveMongo2Mapped) {
            return $object->{{$instance}}->getOriginal();
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

    public function trigger($w, $event, $object, Array $args = array())
    {
        if (!$w) return;
        if ($object instanceof \ActiveMongo2\Reference) {
            $class = strtolower($object->getClass());
        } else {
            $class = strtolower($this->get_class($object));
        }
        $method = "event_{$event}_" . sha1($class);
        if (!is_callable(array($this, $method))) {
            return;
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
            @if ($collection->is('GridFs'))
            case $this->ns_by_name[{{@$collection->getname()}}] . {{@$collection->getName() . '.files'}}:
            case $this->ns_by_name[{{@$collection->getname()}}] . {{@$collection->getName() . '.chunks'}}:
            case {{@$collection->getName() . '.files'}}:
            case {{@$collection->getName() . '.chunks'}}:
            @else
            @if ($collection->getName())
                case $this->ns_by_name[{{@$collection->getname()}}] . {{@$collection->getName()}}:
            @end
            case {{@$collection->getName()}}:
            @end
                @if (!$collection->is('SingleCollection'))
                    $class = {{$collection->getClassCode()}};
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

        return $class;
    }

    public function get_class($object)
    { 
        if ($object instanceof \ActiveMongo2\Reference) {
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

    public function ensureIndex($background)
    {

        @set($is_new, version_compare(MongoClient::VERSION, '1.5.0', '>'))

        @foreach($collections->getIndexes() as $id => $index)
            @set($next, uniqid(true))
            @if (!empty($index['col'])) 
                @set($col, $index['col'])
            @else
                @set($col, $index['prop']->getParent())
            @end

            $conn = $this->class_connections[{{@$col->getClass()}}];
            if (empty($this->connections[$conn])) {
                goto skip_{{$next}};
            }
            $db = $this->connections[$conn];

        try {
            $col = $db->createCollection($this->ns_by_name[{{@$col->getName()}}] . {{@$col->getName()}}); 

            @if ($is_new)
            $return = $col->createIndex(
                {{@.$index['field']}},
                {{@.$index['extra']}}
            );
            @else
            $return = $col->ensureIndex(
                {{@.$index['field']}},
                {{@.$index['extra']}}
            );
            @end
        } catch (\MongoException $e) {
            // delete index and try to rebuild it
            $col->deleteIndex({{@.$index['field']}});

            @if ($is_new)
            $col->createIndex(
                {{@.$index['field']}},
                {{@.$index['extra']}}
            );
            @else
            $col->ensureIndex(
                {{@.$index['field']}},
                {{@.$index['extra']}}
            );
            @end
        }
        skip_{{$next}}:
        @end
    }

    protected function compareObjects($a, $b)
    {
        if ($a === $b) {
            return true;
        }

        if (is_array($a) && is_array($b)) {
            $keysA = array_keys($a);
            $keysB = array_keys($b);
            if ($keysA !== $keysB) {
                return false;
            }
            foreach ($keysA as $key) {
                if (!$this->compareObjects($a[$key], $b[$key])) {
                    return false;
                }
            }
            return true;
        }

        if (!is_object($a) || !is_object($b)) {
            return false;
        }

        $class = get_class($b);
        if (!($a instanceof $class)) {
            return false;
        }

        if ($a instanceof \MongoBinData || $a instanceof \MongoId) {
            return $a->__toString() === $a->__toString();
        }

        if ($a instanceof \MongoDate) {
            return $a->sec === $b->sec && $b->usec === $b->usec;
        }


        return false;
    }


    @foreach ($collections as $collection)

    /**
     *  {{ $collection->getClass() }} => {{ $collection->GetName() }}
     *  {{ count($collection->getAnnotation()->getAnnotations()) }}
     */
    protected function set_property_{{sha1($collection->getClass())}}($object, $name, $value)
    {
        switch ($name) {
        @foreach ($collection->getProperties() as $prop)
        case {{@$prop->getPHPName()}}:
        case {{@$prop->getName()}}:
            @if ($prop->isPublic())
                $object->{{ $prop->getPHPName() }} = $value;
            @else
                $property = new \ReflectionProperty($object, {{ @$prop->getPHPName() }});
                $property->setAccessible(true);
                $property->setValue($object, $value);
            @end
            break;
        @end
        default:
            throw new \RuntimeException("Missing property {$name}");
        }

        return true;
    }

    /**
     *  Populate from $_POST for collection {{$collection->GetClass()}}
     */
    protected function populate_from_array_{{sha1($collection->getClass())}}($object, Array $data)
    {
        @if ($collection->GetName())
        if (array_key_exists({{@$collection->GetName()}}, $data)) {
            $data = $data[{{@$collection->getName()}}];
        }
        @end
        
        @if ($collection->GetParent())
        // populate parent data first
        $this->populate_from_array_{{sha1($collection->GetParent()->getClass())}}($object, $data);
        @end

        @foreach ($collection->getProperties() as $prop)
            @if ($prop->isId() || $prop->getAnnotation()->has('ReferenceMany,EmbedMany'))
                // we cannot handle {{$prop->GetName()}} at the moment
                @continue
            @end

            @foreach(array_unique([$prop->getName(), $prop->getPHPName()]) as $var)

            if (array_key_exists({{@$var}}, $data)) {
                $value = $data[{{@$var}}];
                @if ($xcol = $prop->getReferenceCollection()))
                    @set($xclass, $collections->ByName()[$xcol]['class'])
                    @if ($xclass)
                        if (!is_array($value)) {
                            throw new \RuntimeException("{{@$prop->getName()}} must be an array");
                        }
                        if ({{@$prop->getType() == 'Reference'}} && !empty($value['_id'])) {
                            $value = $this->connection->getCollection({{@$xcol}})
                                ->findOne($value['_id']);
                        } else {
                            @if ($prop->isPublic())
                                $oldValue = $object->{{$prop->getPHPName()}};
                            @else
                                $property = new \ReflectionProperty($object, {{@$var}});
                                $property->setAccessible(true);
                                $oldValue = $property->getValue($object);
                            @end
                            $docValue =  $oldValue ?: new \{{$xclass}};
                            $this->populate_from_array_{{sha1($xclass)}}($docValue, $value);
                            $value = $docValue;
                        }
                    @end
                @end 

                @if ($prop->isPublic())
                    $object->{{$prop->getPHPName()}} = $value; 
                @else
                    $property = new \ReflectionProperty($object, {{@$prop->getPHPName()}});
                    $property->setAccessible(true);
                    $property->setValue($object, $value);
                @end
    
            }
            @end
        @end
    }


    protected function get_property_{{sha1($collection->getClass())}}($object, $name)
    {
        switch ($name) {
        @foreach ($collection->getProperties() as $prop)
        case {{@$prop->getPHPName()}}:
        case {{@$prop->getName()}}:
            @if ($prop->isPublic())
                $return = $object->{{ $prop->getPHPName() }};
            @else
                $property = new \ReflectionProperty($object, {{ @$prop->getPHPName() }});
                $property->setAccessible(true);
                $return = $property->getValue($object);
            @end
            break;
        @end
        case '_id': 
            //fallback to get the object ID when it is not part of the object (rare case)
            if (!empty($object->{{$instance}}) && $object->{{$instance}} instanceof ActiveMongo2Mapped) {
                return $object->{{$instance}}->getOriginal()['_id'];
            }
        default:
            @if ($collection->getParent()) 
                return $this->get_property_{{sha1($collection->getParent())}}($object, $name);
            @else
                throw new \RuntimeException("Missing property {$name}");
            @end
        }

        return $return;
    }

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

        @foreach ($collection->getProperties() as $prop)
            $has_changed = false;
            if (array_key_exists({{@$prop.''}}, {{$prop->getPHPBaseVariable('$current')}})
                || array_key_exists({{@$prop.''}}, $old)) {
                if (!array_key_exists({{@$prop.''}}, {{$prop->getPHPBaseVariable('$current')}})) {
                    $change['$unset'][{{@$prop.''}}] = 1;
                } else if (!array_key_exists({{@$prop.''}}, $old)) {
                    $change['$set'][{{@$prop.''}}] = {{$prop->getPHPVariable('$current')}};
                    $has_changed = true;
                } else if (!$this->compareObjects({{$prop->getPHPVariable('$current')}}, $old[{{@$prop.''}}])) {
                    $has_changed = true;

                    @if ($prop->getAnnotation()->has('Inc'))
                        if (empty($old[{{@$prop.''}}])) {
                            $prev = 0;
                        } else {
                            $prev = $old[{{@$prop.''}}];
                        }
                        $change['$inc'][{{@$prop.''}}] = {{$prop->GetPHPVariable('$current')}} - $prev;
                    @elif ($prop->getAnnotation()->has('Embed'))
                        if ({{$prop->getPHPVariable('$current')}}['__embed_class'] != $old[{{@$prop.''}}]['__embed_class']) {
                            $change['$set'][{{@$prop.'.'}} . $index] = {{$prop->GetPHPVariable('$current')}};
                        } else {
                            $update = 'update_' . sha1({{$prop->getPHPVariable('$current')}}['__embed_class']);
                            $diff = $this->$update({{$prop->getPHPVariable('$current')}}, $old[{{@$prop.''}}], true);
                            foreach ($diff as $op => $value) {
                                foreach ($value as $p => $val) {
                                    $change[$op][{{@$prop.'.'}} . $p] = $val;
                                }
                            }
                        }
                    @elif ($prop->getAnnotation()->has('EmbedMany'))
                        // add things to the array
                        $toRemove = array_diff_key($old[{{@$prop.''}}], {{$prop->getPHPVariable('$current')}});

                        if (count($toRemove) > 0 && $this->array_unique($old[{{@$prop.''}}], $toRemove)) {
                            $change['$set'][{{@$prop.''}}] = array_values({{$prop->getPHPVariable('$current')}});
                        } else {
                            foreach ({{$prop->getPHPVariable('$current')}} as $index => $value) {
                                if (!array_key_exists($index, $old[{{@$prop.''}}])) {
                                    $change['$push'][{{@$prop.''}}]['$each'][] = $value;
                                    continue;
                                }
                                if ($value['__embed_class'] != $old[{{@$prop.''}}][$index]['__embed_class']) {
                                    $change['$set'][{{@$prop.'.'}} . $index] = $value;
                                } else {
                                    $update = 'update_' . sha1($value['__embed_class']);
                                    $diff = $this->$update($value, $old[{{@$prop.''}}][$index], true);
                                    foreach ($diff as $op => $value) {
                                        foreach ($value as $p => $val) {
                                            $change[$op][{{@$prop.'.'}} . $index . '.' . $p] = $val;
                                        }
                                    }
                                }
                            }

                            foreach ($toRemove as $value) {
                                if (!empty($value['__instance'])) {
                                    $change['$pull'][{{@$prop.''}}]['__instance']['$in'][] = $value['__instance'];
                                } else {
                                    $change['$pull'][{{@$prop.''}}][] = $value;
                                }
                            }
                        }
                    @elif ($prop->getAnnotation()->has('ReferenceMany,Array'))
                        // add things to the array
                        $toRemove = array_diff_key($old[{{@$prop.''}}], {{$prop->getPHPVariable('$current')}});

                        if ((count($toRemove) > 0 && $this->array_unique($old[{{@$prop.''}}], $toRemove)) || !$this->is_array($old[{{@$prop.''}}])) {
                            $change['$set'][{{@$prop.''}}] = array_values({{$prop->getPHPVariable('$current')}});
                        } else {
                            foreach ({{$prop->getPHPVariable('$current')}} as $index => $value) {
                                if (!array_key_exists($index, $old[{{@$prop.''}}])) {
                                    @if ($prop->getAnnotation()->has('ReferenceMany'))
                                        $change['$addToSet'][{{@$prop.''}}]['$each'][] = $value;
                                    @else
                                        $change['$push'][{{@$prop.''}}]['$each'][] = $value;
                                    @end
                                    continue;
                                }

                                if (!empty($old[{{@$prop.''}}][$index]['__instance']) && is_array($value)) {
                                    // __instance is an internal variable that helps
                                    // activemongo2 to remove sub objects from arrays easily.
                                    // Its value is private to the library and it shouldn't change
                                    // unless the value of the object changes
                                    $diff = $this->array_diff(
                                        $value,
                                        $old[{{@$prop.''}}][$index]
                                    );
                                    if (count($diff) == 1 && !empty($diff['__instance'])) {
                                        $value['__instance'] = $old[{{@$prop.''}}][$index]['__instance'];
                                        {{$prop->getPHPVariable('$current')}}[$index] = $value;
                                    }
                                }

                                if ($old[{{@$prop.''}}][$index] != $value) {
                                    $change['$set'][{{@$prop . '.'}} . $index] = $value;
                                }
                            }

                            foreach ($toRemove as $value) {
                                if (!empty($value['__instance'])) {
                                    $change['$pull'][{{@$prop.''}}]['__instance']['$in'][] = $value['__instance'];
                                } else {
                                    $change['$pull'][{{@$prop.''}}] = $value;
                                }
                            }
                        }
                    @else
                        $change['$set'][{{@$prop.''}}] = {{$prop->getPHPVariable('$current')}};
                    @end
                }
            }

            @set($ann, $prop->getAnnotation())
            @if ($ann->has('Array,ReferenceMany,EmbedMany'))
                @if ($ann->has('Limit'))
                if ($has_changed && !empty($change['$push'][{{@$prop.''}}])) {
                    $change['$push'][{{@$prop.''}}]['$slice'] = {{@0+current($prop->getAnnotation()->getOne('Limit')->getArgs())}};
                }
                @end
                @if ($ann->has('Sort'))
                if ($has_changed && !empty($change['$push'][{{@$prop.''}}])) {
                    $change['$sort'][{{@$prop.''}}]['$sort'] = {{@0+current($prop->getAnnotation()->getOne('Limit')->getArgs())}};
                }
                @end
            @end
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
        @if ($p = $collection->getParent())
            $this->populate_{{sha1($p->getClass())}}($object, $data);
        @end

        @if ($collection->is('GridFs'))
            if (!$data instanceof \MongoGridFsFile) {
                throw new \RuntimeException("Internal error, trying to populate a GridFSFile with an array");
            }
            $data_file = $data;
            $data      = $data->file;
            if (empty($data['metadata'])) {
                $data['metadata'] = [];
            }
            @foreach (array("length", "chunkSize", "md5", "uploadDate") as $key)
                $data['metadata'][{{@$key}}] = $data[{{@$key}}];
            @end
        @else

            if (!is_array($data)) {
                throw new \RuntimeException("Internal error, trying to populate a document with a wrong data");
            }
        @end

        $doc = $data;

        @foreach ($collection->getProperties() as $prop)
            @if ($prop->getAnnotation()->has('ReferenceMany'))
                if (!empty({{$prop->getPHPVariable()}})) {
                    foreach({{$prop->getPHPVariable()}} as $id => $sub) {
                        if (empty($sub['__instance']) || !strpos($sub['__instance'], $sub['$ref'])) {
                            $sub['__instance'] = $sub['$ref'] . ':' . serialize($sub['$id']) ;
                        }
                        {{$prop->getPHPVariable()}}[$id] = $sub;
                    }
                }
            @elif ($prop->getAnnotation()->has('Stream'))
                @if ($prop->isPublic())
                    $object->{{$prop->getPHPName()}} = $data_file->getResource();
                @else
                    $property = new \ReflectionProperty($object, {{@$prop->getPHPName()}});
                    $property->setAccessible(true);
                    $property->setValue($object, $data_file->getResource());
                @end
                @continue
            @end

            if (array_key_exists({{@$prop.''}}, {{$prop->getPHPBaseVariable()}})) {
                @foreach ($prop->getCallback('Hydratate') as $h)
                    {{ $h->toCode($prop, $prop->getPHPVariable()) }}
                @end

                @if ($prop->isPublic())
                    $object->{{$prop->getPHPName()}} = {{$prop->getPHPVariable()}};
                @else
                    $property = new \ReflectionProperty($object, {{@$prop->getPHPName()}});
                    $property->setAccessible(true);
                    $property->setValue($object, {{$prop->getPHPVariable()}});
                @end
                
            }
        @end

        if (empty($object->{{$instance}})) {
            $object->{{$instance}} = new ActiveMongo2Mapped({{$collection->getClassCode()}}, $data);
        } else {
            $object->{{$instance}}->{{$instance}}_setOriginal($data);
        }
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
                '$ref'  => {{@$collection->getName()}}, 
                '$id'   => $document['_id'],
                '__class' => {{$collection->getClassCode()}},
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
            @if ($prop->isPublic() && !$prop->isCustom())
                /* Public property {{$prop->getPHPName()}} -> {{$prop->getName()}} */
                if ($object->{{$prop->getPHPName()}} !== NULL) {
                    {{ $prop->getPHPVariable()}} = $object->{{ $prop->getPHPName() }};
                }
            @elif ($prop->isCustom())
                /* public and custom property {{$prop->getPHPName()}} -> {{$prop->getName()}} */
                if (!empty($object->{{$prop->getPHPName()}})) {
                    {{ $prop->getPHPVariable()}} = $object->{{ $prop->getPHPName() }};
                }
            @else
                $property = new \ReflectionProperty($object, {{ @$prop->getPHPName() }});
                $property->setAccessible(true);
                $value = $property->getValue($object);
                if ($value !== NULL) {
                    {{$prop->getPHPVariable()}} = $value;
                }
            @end
        @end

        @foreach ($collection->getProperties() as $prop)
            @foreach($prop->getCallback('DefaultValue') as $default)
                if (empty({{$prop->getPHPVariable()}})) {
                    {{$default->toCode($prop)}}
                    {{$prop->getPHPVariable()}} = $return;
                }
            @end
        @end

        @if ($collection->is('SingleCollection'))
            // SINGLE COLLECTION
            {{$collection->getDiscriminator(true)->getPHPVariable()}} = {{$collection->getClassCode()}};
        @end 

        if (empty($doc['_id'])) {
            $oldDoc = $this->getRawDocument($object, false);
            if (!empty($oldDoc['_id'])) {
                $doc['_id'] = $oldDoc['_id'];
            }
        }

        return $doc;
    }

    protected function reflect_{{sha1($collection->getClass())}}() 
    {
        $reflection = array(
            'class'    => {{$collection->getClassCode()}},
            'name'     => {{@$collection->getName()}},
            'collection'     => {{@$collection->getName()}},
            'annotation' => array(
        @foreach ($collection->getAnnotation()->getAnnotations() as $ann) 
            new Annotation({{@$ann->getName()}}, {{@$collection->serializeAnnArgs($ann)}}),
        @end
            ),
            'properties'  => array(
        @foreach ($collection->getProperties() as $prop) 
            {{@$prop->getPHPName()}} => new \ActiveMongo2\Reflection\Property(array(
                'property' => {{@$prop.''}},
                'type'     => {{@$prop->getType()}},
                @if ($prop->getReferenceCollection())
                'collection' => {{@$prop->getReferenceCollection()}},
                @end
                'annotation' => new Annotations(array(
                    @foreach ($prop->getAnnotation()->getAnnotations() as $ann)
                    new Annotation({{@$ann->getName()}}, {{@$collection->serializeAnnArgs($ann)}}),
                    @end
                )),
            ), $this),
        @end
        ));

        @if ($collection->getParent()) {
            $reflection['properties'] = array_merge(
                $this->reflect_{{sha1($collection->GetParent())}}()['properties'], 
                $reflection['properties']
            );
        @end
        return $reflection;
    }

    /**
     *  Validate {{$collection->getClass()}} object
     */
    protected function validate_{{sha1($collection->getClass())}}(\{{$collection->getClass()}} $object, Array $old)
    {
        @if ($collection->getParent())
            $doc = array_merge(
                $this->validate_{{sha1($collection->getParent())}}($object, $old),
                $this->get_array_{{sha1($collection->getClass())}}($object, false)
            );
        @else 
            $doc = $this->get_array_{{sha1($collection->getClass())}}($object);
        @end

        @foreach ($collection->getProperties() as $prop)
            @if ($prop->getAnnotation()->has('Required'))
            if (empty({{$prop->getPHPVariable()}})) {
                throw new \RuntimeException("{{$prop.''}} cannot be empty");
            }
            @end
            if (!empty({{$prop->getPHPVariable()}}) &&
                   (empty({{$prop->getPHPVariable('$old')}}) ||
                    {{$prop->getPHPVariable()}} !== {{$prop->getPHPVariable('$old')}} )) {
                                                                                            
                @foreach ($prop->getCallback('Validate') as $val)
                    @if (!$val->isLast())
                    {{$val->toCode($prop, $prop->getPHPVariable())}}
                    if ($return === FALSE) {
                        throw new \RuntimeException("Validation failed for {{$prop.''}}");
                    }
                    @end
                @end

                @if ($prop->getAnnotation()->has('Date'))
                    $_date = \date_create('@' . {{$prop->getPHPVariable()}}->sec);
                    if ({{$validator->functionName($collection->getClass(), $prop->getPHPName())}}($_date) === false) {
                        throw new \RuntimeException("Validation failed for {{$prop.''}}");
                    }
                @elif (!$prop->isCustom() && $validator->hasRules($collection->getClass(), $prop->getPHPName()))
                    if ({{$validator->functionName($collection->getClass(),  $prop->getPHPName())}}({{$prop->getPHPVariable()}}) === false) {
                        throw new \RuntimeException("Validation failed for {{$prop.''}}");
                    }
                @end

                @foreach ($prop->getCallback('Validate') as $val)
                    @if ($val->isLast())
                    {{$val->toCode($prop, $prop->getPHPVariable())}}
                    if ($return === FALSE) {
                        throw new \RuntimeException("Validation failed for {{$prop.''}}");
                    }
                    @end
                @end
            } else if (!empty({{$prop->getPHPVariable()}})) {
                // always check
                @foreach ($prop->getCallback('Validate') as $val)
                    @if ($val->isAlwaysCheck())
                    {{$val->toCode($prop, $prop->getPHPVariable())}}
                    if ($return === FALSE) {
                        throw new \RuntimeException("Validation failed for {{$prop.''}}");
                    }
                    @end
                @end
            }
        @end

        return $doc;
    }

    protected function update_property_{{sha1($collection->getClass())}}(\{{$collection->getClass()}} $document, $property, $value)
    {
        @if ($collection->getParent())
            $this->update_property_{{sha1($collection->getParent())}}($document, $property, $value);
        @end
        $iproperty = strtolower($property);
        @foreach ($collection->getProperties() as $prop)
            if ($property ==  {{@$prop.''}}
            @foreach($prop->getAnnotation()->getAnnotations() as $annotation) 
                 || $iproperty == {{@'@'.$annotation->getName()}}
            @end
            ) {
                @if ($prop->isPublic())
                    $document->{{$prop->getPHPName()}} = $value;
                @else
                    $property = new \ReflectionProperty($object, {{@$prop->getPHPNAme() }});
                    $property->setAccessible(true);
                    $property->setValue($document, $value);
                @end
            }
        @end
    }


        @foreach ($collections->getEvents() as $ev)
            @if ($collection->hasEvent($ev))
    /**
     *  Code for {{$ev}} events for objects {{$collection->getClass()}}
     */
        protected function event_{{$ev}}_{{sha1($collection->getClass())}}($document, Array $args)
        {
            $class = $this->get_class($document);
            @if (!$collection->isTrait())
            if ($class != {{$collection->getClassCode()}} && !is_subclass_of($class, {{$collection->getClassCode()}})) {
                throw new \Exception("Class invalid class name ($class) expecting  "  . {{$collection->getClassCode()}});
            }
            @end

            @foreach ($collection->getParentAndTraits() as $parent)
                @if ($parent->hasEvent($ev))
                @set($method, "event_" . $ev . "_" . sha1($parent->getClass()))
                $this->{{$method}}($document, $args);
                @end
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

    @end
}

class ActiveMongo2Mapped
{
    protected $class;
    protected $data;

    public function __construct($name, Array $data)
    {
        $this->class = $name;
        $this->data  = $data;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getOriginal()
    {
        return $this->data;
    }

    public function {{$instance}}_setOriginal(Array $data)
    {
        $this->data = $data;
    }
}

@include('validator')

return array(
    "ns" => {{@trim($namespace, '\\')}},
    "validator" => {{@$valns}},
);
