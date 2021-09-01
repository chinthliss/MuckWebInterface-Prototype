<?php


namespace App\Admin;

class LogManager
{
    /**
     * @return array
     */
    public static function getDates(): array
    {
        $dates = [];
        $files = glob(storage_path('logs/*.log'));
        foreach ($files as $file) {
            $fileName = basename($file);
            array_push($dates, substr($fileName, 8, -4)); // Remove 'laravel-' and '.log'
        }
        return $dates;
    }

    public static function getLogFilePathForDate(string $date): string
    {
        return storage_path('logs/') . 'laravel-' . $date . '.log';
    }
}
