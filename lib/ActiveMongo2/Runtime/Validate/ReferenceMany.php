<?php
namespace ActiveMongo2\Runtime\Validate;

use ActiveMongo2\Runtime\Utils;
use ActiveMongo2\Runtime\Serialize;
use ActiveMongo2\Runtime\Reference as ref;

class ReferenceMany
{
    public static function validate($value, $ann, $connection)
    {
        if (empty($value)) {
            return true;
        }

        if (!is_array($value)) {
            return false;
        }

        $class = current($ann['args']);
        $class = $connection->getDocumentClass($class);

        $ref = Utils::getReflectionClass($class);
        $ann = $ref->getAnnotations();
        if (!$ann->get('Referenceable')) {
            throw new \RuntimeException("$class can not be referenced");
        }

        foreach ($value as $id => $doc) {
            if ($doc instanceof ref) {
                continue;
            }
            if (!($doc instanceof $class)) {
                return false;
            }
        }

        return true;
    }

    public static function transformate($value, $ann, $connection)
    {
        $values = array();
        $class  = current($ann['args']);
        $class  = $connection->getDocumentClass($class);

        $ref = Utils::getReflectionClass($class);
        $ann = $ref->getAnnotations();
        if (!$ann->get('Referenceable')) {
            throw new \RuntimeException("$class can not be referenced");
        }
        
        foreach ($value as $id => $doc) {
            if ($doc instanceof ref) {
                if (!$doc->getObject()) {
                    $values[$id] = $doc->getReference();
                    continue;
                }
                $doc = $doc->getObject();
            }

            if (!is_a($doc, $class)) {
                throw new \RuntimeException("Subdocument {$id} is not a valid object of $class");
            }

            $connection->save($doc);

            $raw = $connection->getRawDocument($doc);
            $ref = array(
                '$ref' => Serialize::getCollection($doc),
                '$id'  => $raw['_id'],
            );

            if ($ann->getOne('Referenceable')) {
                $keys = $ann->getOne('Referenceable');
                $keys = array_combine($keys, $keys);

                $ref['_extra'] = array_intersect_key($raw, $keys);
            }

            $values[$id] = $ref;
        }

        return $values;
    }

}
