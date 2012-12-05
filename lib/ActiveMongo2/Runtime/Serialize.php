<?php
namespace ActiveMongo2\Runtime;

use Notoj\ReflectionClass;

class Serialize
{
    public static function getCollection($class)
    {
        $refl = new ReflectionClass($class);
        $ann  = $refl->getAnnotations();
        if (!$ann->has('Persist')) {
            throw new \RuntimeException("Class " . get_class($object) . ' cannot persist. @Persist annotation is missing');
        }

        $persist = $ann->getOne('Persist');

        if (empty($persist)) {
            $parts = implode("\\", $class);
            return end($parts);
        }

        return current($persist);
    }

    public static function setDocument($object, $document)
    {
        $refl = new ReflectionClass($object);
        $ann  = $refl->getAnnotations();
        if (!$ann->has('Persist')) {
            throw new \RuntimeException("Class " . get_class($object) . ' cannot persist. @Persist annotation is missing');
        }

        foreach ($refl->getProperties() as $property) {
            $property->setAccessible(true);
            $ann  = $property->getAnnotations();
            $name = $property->name;
            if ($ann->has('Id')) {
                $name = '_id';
            }

            $property->setValue($object, $document[$name]);
        }

        return $object;

    }

    public static function getDocument($object) 
    {
        $refl = new ReflectionClass($object);
        $ann  = $refl->getAnnotations();

        if (!$ann->has('Persist')) {
            throw new \RuntimeException("Class " . get_class($object) . ' cannot persist. @Persist annotation is missing');
        }

        $document = array();
        foreach ($refl->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($object); 
            $ann   = $property->getAnnotations();
            foreach ($ann as $annotation) {
                $class = __NAMESPACE__ .  '\\Validator\\' . ucfirst($annotation['method']);
                if (class_exists($class) && !$class::validator($value)) {
                    throw new \RuntimeException("{$class} validation for  \"{$value}\" failed");
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

    public function main()
    {
        /**$obj = (array)new \foobar\User;
        var_dump($obj);
        var_dump($obj["\0foobar\User\0foobar"]);
        exit;*/
    }
}
