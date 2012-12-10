<?php

namespace ActiveMongo2\Runtime\Hydrate;

use ActiveMongo2\Reference as zReference;

class Reference
{
    public static function Hydrate($value, $ann, $connection)
    {
        if (!is_array($value)) {
            return NULL;
        }

        $class = current($ann['args']);
        $class = $connection->getDocumentClass($class);
    
        return new zReference($value, $class, $connection);
    }
}
