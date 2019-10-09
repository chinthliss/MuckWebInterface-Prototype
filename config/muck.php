<?php
return [

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
    'host' => env('HOST', '127.0.0.1'),
    'port' => env('PORT', '8000'),

    /*
    |--------------------------------------------------------------------------
    | Password
    |--------------------------------------------------------------------------
    | More of a handshake challenge but this must be the same as the one the muck is expecting
    */
    'password' => env('PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Use HTTPS?
    |--------------------------------------------------------------------------
    | Whether to use HTTPS. Not generally required if the muck/web live on the same machine
    */
    'useHttps' => ENV('USE_HTTPS', false),
];
