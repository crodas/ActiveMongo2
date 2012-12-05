<?php

namespace ActiveMongo2\Runtime\Validator;

class Email
{
    public static function validator($string)
    {
        return empty($string) || filter_var($string, FILTER_VALIDATE_EMAIL);
    }
}
