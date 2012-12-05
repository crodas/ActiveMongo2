<?php

namespace ActiveMongo2\Runtime\Validator;

class Required
{
    public static function validator($string)
    {
        return !empty($string);
    }
}
