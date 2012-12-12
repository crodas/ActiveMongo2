<?php

namespace ActiveMongo2\Plugin;

use Notoj\Annotation;

class Unupdatable
{
    /**
     *  @preUpdate
     */
    public static function check(Array $args, $object, Array $doc, $conn)
    {
        foreach ($args as $prop) {
            foreach ($doc as $key => $props) {
                if ($key == $prop) {
                    throw new \RuntimeException("{$prop} cannot be updated");
                }
                if (is_array($props) && array_key_exists($prop, $props)) {
                    throw new \RuntimeException("{$prop} cannot be updated");
                }
            }
        }
    }
}
