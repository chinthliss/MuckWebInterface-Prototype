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
    | 5 character string used for storage and places where a shorter code is better
    |
    |
    */

    'muck_code' => env('MUCK_CODE', 'UNSET'),

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
