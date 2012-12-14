<?php

namespace ActiveMongo2\Runtime\Hydrate;

use ActiveMongo2\Runtime\Reference as zReference;
use ActiveMongo2\Runtime\Serialize;

class Reference
{
    public static function Hydrate($value, $ann, $connection)
    {
        if (!is_array($value)) {
            return NULL;
        }

        $class = current($ann['args']);
        $class = $connection->getDocumentClass($class);

        $map = Serialize::getDocummentMapping($class);

        return new zReference($value, $class, $connection, $map);
    }
}
