<?php
namespace ActiveMongo2\Runtime;

class Serialize
{
    public static function getCollection($class)
    {
        $refl = Utils::getReflectionClass($class);
        $ann  = $refl->getAnnotations();
        if (!$ann->has('Persist')) {
            throw new \RuntimeException("Class $class cannot persist. @Persist annotation is missing");
        }

        $persist = $ann->getOne('Persist');

        if (empty($persist)) {
            $parts = explode("\\", $refl->getName());
            return strtolower(end($parts));
        }

        return current($persist);
    }

    public static function getDocummentMapping($class)
    {
        $refl = Utils::getReflectionClass($class);
        $ann  = $refl->getAnnotations();

        $map  = array();
        foreach ($refl->getProperties() as $property) {
            $property->setAccessible(true);
            $ann  = $property->getAnnotations();
            if (!$ann) {
                continue;
            }
            $name  = $property->name;
            if ($ann->has('Id')) {
                $name = '_id';
            }
            $map[$property->name] = $name;
        }

        return $map;
    }

    public static function setDocument($object, $document, $connection)
    {
        $refl = Utils::getReflectionClass($object);
        $ann  = $refl->getAnnotations();
        if (!$ann->has('Persist') && !$ann->has('Embeddable')) {
            throw new \RuntimeException("Class " . get_class($object) . ' cannot persist. @Persist annotation is missing');
        }

        foreach ($refl->getProperties() as $property) {
            $property->setAccessible(true);
            $ann  = $property->getAnnotations();
            if (!$ann) {
                continue;
            }
            $name  = $property->name;
            if ($ann->has('Id')) {
                $name = '_id';
            }

            if (!array_key_exists($name, $document)) {
                continue;
            }
            $value = $document[$name];

            foreach ($ann as $annotation) {
                $class = __NAMESPACE__ .  '\\Hydrate\\' . ucfirst($annotation['method']);
                if (Utils::class_exists($class)) {
                    $value = $class::Hydrate($value, $annotation, $connection);
                }
            }

            $property->setValue($object, $value);
        }

        return $object;

    }

    public static function getDocument($object, $connection) 
    {
        $refl = Utils::getReflectionClass($object);
        $ann  = $refl->getAnnotations();

        if (!$ann->has('Persist') && !$ann->has('Embeddable')) {
            throw new \RuntimeException("Class " . get_class($object) . ' cannot persist. @Persist annotation is missing');
        }

        $document = array();
        foreach ($refl->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($object); 
            $ann   = $property->getAnnotations();
            foreach ($ann as $annotation) {
                $class = __NAMESPACE__ .  '\\Validate\\' . ucfirst($annotation['method']);
                if (Utils::class_exists($class) && !$class::validate($value, $annotation, $connection)) {
                    throw new \RuntimeException("validation for  \"{$class}\" failed for {$property}");
                }

                if (is_callable(array($class, 'transformate'))) {
                    $value = $class::transformate($value, $annotation, $connection);
                    if ($value === NULL) {
                        // means no value
                        continue;
                    }
                }
            }

            $name = $property->name;
            if ($ann->has('Id')) {
                $name = '_id';
            }

            $document[$name] = $value;
        }

        return $document;
    }

    public static function changes($object, $current, $oldDoc)
    {
        $refl = Utils::getReflectionClass($object);
        $ann  = $refl->getAnnotations();

        if (!$ann->has('Persist') && !$ann->has('Embeddable')) {
            throw new \RuntimeException("Class " . get_class($object) . ' cannot persist. @Persist annotation is missing');
        }

        $document = array();
        foreach ($refl->getProperties() as $property) {
            $name = $property->name;
            $ann  = $property->getAnnotations();
            if ($ann->has('Id')) {
                continue;
            }

            if (array_key_exists($name, $current) && array_key_exists($name, $oldDoc) && $current[$name] === $oldDoc[$name]) {
                continue;
            }

            if (array_key_exists($name, $current)) {
                if ($ann->has('Inc')) {
                    if (empty($oldDoc[$name])) {
                        $oldDoc[$name] = 0;
                    }
                    $document['$inc'][$name] = $current[$name] - $oldDoc[$name];
                } else if ($ann->has('Embed') && array_key_exists($name, $oldDoc)) {
                    $doc = self::changes($object->{$name}[$index], $current[$name][$index], $oldDoc[$name][$index]);
                    foreach ($doc as $op => $update) {
                        foreach ($update as $key => $val) {
                            $document[$op][$name . ".$index." . $key] = $val;
                        }
                    }
                } else if ($ann->has('EmbedMany') && array_key_exists($name, $oldDoc)) {
                    foreach ($current[$name] as $index => $value) {
                        if (!array_key_exists($index, $oldDoc[$name])) {
                            $document['$push'][$name] = $value;
                            continue;
                        }
                        $doc = self::changes($object->{$name}[$index], $current[$name][$index], $oldDoc[$name][$index]);
                        foreach ($doc as $op => $update) {
                            foreach ($update as $key => $val) {
                                $document[$op][$name . ".$index." . $key] = $val;
                            }
                        }
                    }
                } else {
                    $document['$set'][$name] = $current[$name];
                }
            }
        }

        return $document;
    }

    public function main()
    {
        /**$obj = (array)new \foobar\User;
        var_dump($obj);
        var_dump($obj["\0foobar\User\0foobar"]);
        exit;*/
    }
}
