<?php

namespace ActiveMongo2\Runtime\Hydrate;

class Embed
{

    public static function Hydrate($value, $ann, $connection)
    {
        if (!is_array($value)) {
            return NULL;
        }
        $class = current($ann['args']);
        $class = $connection->getDocumentClass($class);

        return $connection->registerDocument($class, $value);
    }
}
