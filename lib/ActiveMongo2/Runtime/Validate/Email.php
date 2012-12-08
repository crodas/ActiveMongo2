<?php

namespace ActiveMongo2\Runtime\Validate;

class Email
{
    public static function validate($string)
    {
        return empty($string) || filter_var($string, FILTER_VALIDATE_EMAIL);
    }
}
