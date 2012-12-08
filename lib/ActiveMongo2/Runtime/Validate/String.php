<?php

namespace ActiveMongo2\Runtime\Validate;

class String
{
    public static function validate($string)
    {
        return empty($string) || is_string($string);
    }
}
