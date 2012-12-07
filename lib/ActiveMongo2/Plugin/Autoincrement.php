<?php

namespace ActiveMongo2\Plugin;

use Notoj\Annotation;

/** @Persist(table="_autoincrement") */
class Autoincrement
{
    /** @Id */
    protected $collection;

    /** @Int */
    protected $lastId;

    /**
     *  @preCreate
     */
    public static function setCollectionId(Array $args, $object, Array &$document, $conn)
    {
        if (empty($document['_id'])) {
            $doc = $doc = $doc = $doc = $conn->getCollection(__CLASS__)->findAndModify(
                array('_id' => get_class($object)),
                array('$inc' => array('lastId' => 1)),
                array('upsert' => true, 'new' => true)
            );

            $document['_id'] = $doc->lastId;
        }
    }
}
