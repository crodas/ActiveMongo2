<?php

namespace ActiveMongo2\Runtime\Validator;

class String
{
    public static function validator($string)
    {
        return empty($string) || is_string($string);
    }
}
