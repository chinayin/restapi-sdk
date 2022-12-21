<?php

namespace RestAPI;

abstract class Domain
{
    public static $DOMAIN_MAIN = 'dV9oX29fbV9lX3M=';
    public static $IV_HASH = 'dV9faF9fb19fbV9fZV9fc19jX29fbV90X2lfYV9uX2xfZV9p';

    public static function getDomain($str)
    {
        return str_replace(['_'], '', base64_decode($str));
    }

    public static function getMainDomain()
    {
        return self::getDomain(self::$DOMAIN_MAIN);
    }

    public static function getIVHash(){
        return self::getDomain(self::$IV_HASH);
    }

}
