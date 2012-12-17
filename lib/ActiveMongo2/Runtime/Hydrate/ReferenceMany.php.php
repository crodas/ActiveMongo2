<?php

namespace ActiveMongo2\Runtime\Hydrate;

use ActiveMongo2\Runtime\Reference as zReference;
use ActiveMongo2\Runtime\Serialize;

class ReferenceMany
{
    public static function Hydrate($values, $ann, $connection)
    {
        if (!is_array($values)) {
            return NULL;
        }

        $class = current($ann['args']);
        $class = $connection->getDocumentClass($class);

        $docs = array();
        $map  = Serialize::getDocummentMapping($class);
        foreach ($values as $id => $value) {
            $docs[$id] = new zReference($value, $class, $connection, $map);
        }

        return $docs;
    }
}
