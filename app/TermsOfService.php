<?php


namespace App;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TermsOfService
{
    private static $hash = null;

    public static function getTermsOfServiceFilepath(): string
    {
        return public_path('terms-of-service.txt');
    }

    public static function getTermsOfServiceHash(): string
    {
        if (!self::$hash) {
            self::$hash = Cache::get('tos-hash');
            //Check in case we need to (re)generate such
            $lastSeenModification = Cache::get('tos-last-seen-modification');
            try {
                if ($lastSeenModification) $lastSeenModification = new Carbon($lastSeenModification);
            } catch (\Throwable $e) {
                Log::warning("Unable to read Terms of Service's last seen modification from cache.");
            }
            $lastModification = Carbon::createFromTimeStamp(filemtime(self::getTermsOfServiceFilepath()));
            if (!self::$hash || !$lastSeenModification || $lastModification > $lastSeenModification) {
                Log::info("Regenerating Terms of Service hash.");
                //One day maybe switch to this:
                //self::$hash = md5_file('../public/terms-of-service.txt');
                //But at present, this matches how the muck calculates it:
                $fileContents = join('', self::getTermsOfService());
                self::$hash = md5($fileContents);
                Cache::forever('tos-hash', self::$hash);
                Cache::forever('tos-last-seen-modification', $lastModification);
            }
        }
        return self::$hash;
    }

    public static function getTermsOfService()
    {
        return file(self::getTermsOfServiceFilepath(), FILE_IGNORE_NEW_LINES);
    }
}
