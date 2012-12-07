<?php

namespace ActiveMongo2\Runtime;

use Notoj\ReflectionClass;

class Utils
{
    static private $classes = array();
    static private $reflections = array();

    public static function class_exists($class)
    {
        if (array_key_exists($class, self::$classes)) {
            return self::$classes[$class];
        }
        return self::$classes[$class] = class_exists($class);
    }

    public static function getReflectionClass($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        if (empty(self::$reflections[$class])) {
            self::$reflections[$class] = new ReflectionClass($class);
        }

        return self::$reflections[$class];
    }
}
