<?php

namespace ActiveMongo2\Runtime\Validate;

use ActiveMongo2\Runtime\Serialize;

class Embed
{
    public static function validate(&$value, $ann, $connection)
    {
        if (empty($value)) {
            return true;
        }

        $class = current($ann['args']);
        $class = $connection->getDocumentClass($class);
        if (!is_a($value, $class)) {
            throw new \RuntimeException("Element {$id} is not an object of {$class}");
        }

        $value = Serialize::getDocument($value, $connection);

        return true;
    }
}
