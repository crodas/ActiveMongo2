<?php
namespace ActiveMongo2\Runtime\Validate;

use ActiveMongo2\Runtime\Utils;
use ActiveMongo2\Runtime\Serialize;

class Reference
{
    public static function validate($value, $ann, $connection)
    {
        $class = current($ann['args']);
        $class = $connection->getDocumentClass($class);

        if (is_a($value, 'ActiveMongo2\\Reference')) {
            return true;
        }
        
        if (!is_a($value, $class)) {
            return false;
        }

        $ref = Utils::getReflectionClass($class);
        $ann = $ref->getAnnotations();
        if (!$ann->get('Referenceable')) {
            throw new \RuntimeException("$class can not be referenced");
        }

        return true;
    }

    public static function transformate($value, $ann, $connection)
    {
        if (is_a($value, 'ActiveMongo2\Reference')) {
            if (!$value->getObject()) {
                return $value->getReference();
            }
            $value = $value->getObject();
        }

        $class = current($ann['args']);
        $class = $connection->getDocumentClass($class);
        
        if (!is_a($value, $class)) {
            return false;
        }

        $ref = Utils::getReflectionClass($class);
        $ann = $ref->getAnnotations();
        if (!$ann->get('Referenceable')) {
            throw new \RuntimeException("$class can not be referenced");
        }

        $connection->save($value);

        $doc = $connection->getRawDocument($value);

        $ref = array(
            '$ref' => Serialize::getCollection($value),
            '$id'  => $doc['_id'],
        );
        if ($ann->getOne('Referenceable')) {
            $keys = $ann->getOne('Referenceable');
            $keys = array_combine($keys, $keys);

            $ref['_extra'] = array_intersect_key($doc, $keys);
        }

        return $ref;
    }

}
