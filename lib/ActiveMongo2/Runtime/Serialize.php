<?php
namespace ActiveMongo2\Runtime;

class Serialize
{
    public static function getCollection($class)
    {
        $refl = Utils::getReflectionClass($class);
        $ann  = $refl->getAnnotations();
        if (!$ann->has('Persist')) {
            throw new \RuntimeException("Class " . get_class($object) . ' cannot persist. @Persist annotation is missing');
        }

        $persist = $ann->getOne('Persist');

        if (empty($persist)) {
            $parts = implode("\\", $class);
            return strtolower(end($parts));
        }

        return current($persist);
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
