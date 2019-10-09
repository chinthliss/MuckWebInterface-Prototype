<?php


namespace App\Helpers;


class MuckInterop
{
    //Taken from https://stackoverflow.com/questions/14674834/php-convert-string-to-hex-and-hex-to-string
    public static function strToHex(string $string)
    {
        return implode(unpack("H*", $string));

    }

    //Taken from https://stackoverflow.com/questions/14674834/php-convert-string-to-hex-and-hex-to-string
    public static function hexToStr(string $hex)
    {
        return pack("H*", $hex);
    }

    //Here since it's used in at least two places and didn't want to copy/paste
    public static function createSHA1SALTPassword(string $password)
    {
        $password = substr($password, 0, 128);
        $saltCharacters = [];
        for ($i = 0; $i < 7; $i++) array_push($saltCharacters, dechex(rand(0, 255)));
        $salt = implode($saltCharacters);
        return $salt . ':' . sha1(MuckInterop::hexToStr($salt) . $password);
    }

    //Here since it's used in at least two places and didn't want to copy/paste
    public static function verifySHA1SALTPassword(string $passwordToVerify, string $SHA1SALT)
    {
        if (strpos($SHA1SALT, ':') === false) return false;
        list($salt, $password) = explode(':', $SHA1SALT);
        return strcasecmp($password, sha1(MuckInterop::hexToStr($salt) . substr($passwordToVerify, 0, 128))) == 0;
    }

}
