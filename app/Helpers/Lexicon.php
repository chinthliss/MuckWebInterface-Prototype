<?php


namespace App\Helpers;


use Illuminate\Support\Facades\Log;

class Lexicon
{
    /**
     * @var null|array[string]
     */
    private static $dictionary = null;

    public static function loadDictionary()
    {
        self::$dictionary = config('lexicon');
        //Special cases taken from other parts of the config, so they can be passed to the JS side easily
        self::$dictionary['app_name'] = config('app.name');
        self::$dictionary['game_name'] = config('muck.name');
        self::$dictionary['game_code'] = config('muck.code');
    }
    /**
     * Translates a word into whatever this particular game uses
     * @param string $word
     * @return string $translatedWord
     */
    public static function get(string $word) : string
    {
        if (is_null(self::$dictionary)) self::loadDictionary();
        if (array_key_exists($word, self::$dictionary)) return self::$dictionary[$word];
        Log::debug("Lexicon - An attempt was made to translate an unrecognized phrase/word: {$word}");
        return $word;
    }

    /**
     * Function to export, so it can be loaded into JS
     * @return array
     */
    public static function toArray() : array
    {
        return self::$dictionary;
    }
}
