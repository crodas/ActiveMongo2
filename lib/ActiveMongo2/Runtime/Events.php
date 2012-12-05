<?php
namespace ActiveMongo2\Runtime;

use Notoj\ReflectionClass;

class Events
{
    private static function dispatch($action, ReflectionClass $class, $object, Array $args)
    {
        foreach ($class->getMethods() as $method) {
            $ann = $method->getAnnotations();
            if ($ann->has($action)) {
                call_user_func_array(array($object, $method->getName()), $args);
            }
        }
    }

    public static function run($action, $object, $args = array())
    {
        $class = new ReflectionClass($object);
        self::dispatch($action, $class, $object, $args);

        while ($class = $class->getParentClass()) {
            self::dispatch($action, $class, $object, $args);
        }

    }
}
