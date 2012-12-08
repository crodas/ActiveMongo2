<?php

namespace ActiveMongo2\Runtime\Hydrate;

class EmbedMany
{

    public static function Hydrate($value, $ann, $connection)
    {
        if (!is_array($value)) {
            return NULL;
        }
        $class = current($ann['args']);
        $class = $connection->getDocumentClass($class);

        foreach ($value as $id => $doc) {
            $value[$id] = $connection->registerDocument($class, $doc);
        }

        return $value;
    }
}
