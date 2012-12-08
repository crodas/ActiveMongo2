<?php

namespace ActiveMongo2\Runtime\Validate;

use ActiveMongo2\Runtime\Serialize;

class EmbedMany
{
    public static function validate(&$value, $ann, $connection)
    {
        if (!is_array($value)) {
            return false;
        }
        $class = current($ann['args']);
        $class = $connection->getDocumentClass($class);
        foreach ($value as $id => $doc) {
            if (!is_a($doc, $class)) {
                throw new \RuntimeException("Element {$id} is not an object of {$class}");
            }
            $value[$id] = Serialize::getDocument($doc, $connection);
        }

        return true;
    }
}
