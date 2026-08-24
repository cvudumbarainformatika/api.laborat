<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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
    'orthanc' => [
        'url' => env('URL_WEBHOOK_ORTHANC'),
        'api_key' => env('ORTHANC_API_KEY', 'BismillahRadiologi2026'),
    ],

    'bpjs' => [
        'cons_id' => env('BPJS_CONS_ID', '31014'),
        'secret_key' => env('BPJS_SECRET_KEY', '3sY5CB0658'),
        'vclaim_user_key' => env('BPJS_VCLAIM_USER_KEY', 'fbad382d69383c78969f889077053ebb'),
        'antrean_user_key' => env('BPJS_ANTREAN_USER_KEY', 'f5abd04a8fadc1061e8853715662c3e8'),
        'base_url' => env('BPJS_BASE_URL', 'https://apijkn.bpjs-kesehatan.go.id/'),
        'base_url_dev' => env('BPJS_BASE_URL_DEV', 'https://apijkn-dev.bpjs-kesehatan.go.id/'),
    ],

];
