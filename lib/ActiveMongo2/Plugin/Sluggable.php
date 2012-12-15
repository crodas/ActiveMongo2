<?php

namespace ActiveMongo2\Plugin;

use Notoj\Annotation;

class Sluggable
{
    public static function sluggify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     *  @preCreate
     */
    public static function setSlugUrl(Array $args, $object, Array &$document, $conn)
    {
        if (count($args) != 2) {
            throw new \RuntimeException("@Sluggable expects two arguments");
        }

        $source = self::sluggify($document[$args[0]]);
        $col = $conn->getCollection(get_class($object));

        $slug = self::sluggify($document[$args[0]]);

        while ( $col->count(array($args[1] => $slug)) != 0) {
            $slug .= '-' . uniqid(true);
        }

        $document[$args[1]] = $slug;
    }
}
