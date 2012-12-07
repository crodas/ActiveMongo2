<?php

namespace ActiveMongo2\Runtime;

class Utils
{
    static private $classes = array();
    public static function class_exists($class)
    {
        if (array_key_exists($class, self::$classes)) {
            return self::$classes[$class];
        }
        return self::$classes[$class] = class_exists($class);
    }
}
