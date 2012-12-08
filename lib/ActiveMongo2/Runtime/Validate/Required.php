<?php

namespace ActiveMongo2\Runtime\Validate;

class Required
{
    public static function validate($string)
    {
        return !empty($string);
    }
}
