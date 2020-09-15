<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, SparkPost and others. This file provides a sane default
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'authorizenet' => [
        'loginId' => env('AUTHORIZENET_PAYMENT_API_LOGIN_ID'),
        'transactionKey' => env('AUTHORIZENET_PAYMENT_TRANSACTION_KEY'),
        'sealId' => env('AUTHORIZENET_PAYMENT_SEAL_ID')
    ],

    'paypal' => [
        'account' => env('PAYPAL_ACCOUNT'),
        'clientId' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'subscriptionId' => env('PAYPAL_SUBSCRIPTION_PRODUCT_ID')
    ],

];
