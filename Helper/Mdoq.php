<?php
namespace Mdoq\Connector\Helper;

class Mdoq
{
    private static $isMdoqEnvironment = null;

    public static function isEnvironmentMdoq()
    {
        if(self::$isMdoqEnvironment === null){
            self::$isMdoqEnvironment = (bool)(isset($_SERVER['SERVER_NAME']) && preg_match('/\.mdoq\.io$/', $_SERVER['SERVER_NAME']) === 1);
        }
        return self::$isMdoqEnvironment;
    }
}