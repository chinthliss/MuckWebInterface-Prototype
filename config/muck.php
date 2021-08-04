<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Muck Name
    |--------------------------------------------------------------------------
    |
    | The name for this instance
    |
    |
    */

    'muck_name' => env('MUCK_NAME', 'Unnamed'),

    /*
    |--------------------------------------------------------------------------
    | Muck Code
    |--------------------------------------------------------------------------
    |
    | Integer representing the muck, used in various tables.
    | Should match get_game_id in intermuck
    |
    */

    'muck_code' => env('MUCK_CODE', -1),

    /*
    |--------------------------------------------------------------------------
    | Muck Driver
    |--------------------------------------------------------------------------
    |
    | The driver used to talk to the muck
    |
    | Supported: "http", "fake"
    |
    */

    'driver' => env('MUCK_DRIVER', 'http'),

    /*
    |--------------------------------------------------------------------------
    | Connection details
    |--------------------------------------------------------------------------
    |
    */
    'host' => env('MUCK_HOST', '127.0.0.1'),
    'port' => env('MUCK_PORT', '8000'),
    'uri' => env('MUCK_URI', 'mwi/gateway'),

    /*
    |--------------------------------------------------------------------------
    | Salt
    |--------------------------------------------------------------------------
    | Used to sign requests passed to the muck. Must match the value stored on the muck.
    */
    'salt' => env('MUCK_SALT'),

    /*
    |--------------------------------------------------------------------------
    | Use HTTPS?
    |--------------------------------------------------------------------------
    | Whether to use HTTPS. Not generally required if the muck/web live on the same machine
    */
    'useHttps' => ENV('MUCK_USE_HTTPS', false),
];
