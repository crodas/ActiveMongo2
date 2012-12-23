<?php
namespace ActiveMongo2\Runtime\Validate;

use ActiveMongo2\Runtime\Utils;
use ActiveMongo2\Runtime\Serialize;
use ActiveMongo2\Runtime\Reference as ref;

class Reference
{
    public static function validate($value, $ann, $connection)
    {
        if (empty($value)) {
            return true;
        }
        if ($value instanceof ref) {
            return true;
        }
        

        if ($ann['args']) {
            $class = current($ann['args']);
            $class = $connection->getDocumentClass($class);

            if (!($value instanceof $class)) {
                return false;
            }
        } else {
            $class = get_class($value);
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
        if ($value instanceof ref) {
            if (!$value->getObject()) {
                return $value->getReference();
            }
            $value = $value->getObject();
        }

        if ($ann['args']) {
            $class = current($ann['args']);
            $class = $connection->getDocumentClass($class);
        
            if (!is_a($value, $class)) {
                return NULL;
            }
        } else {
            $class = get_class($value);
        }

        $ref = Utils::getReflectionClass($class);
        $ann = $ref->getAnnotations();

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

        if (!$ann['args']) {
            $ref['_class'] = $class;
        }

        return $ref;
    }

}
