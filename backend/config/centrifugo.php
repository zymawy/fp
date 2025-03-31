<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Centrifugo Server
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the Centrifugo server connection
    |
    */
    'host' => env('CENTRIFUGO_HOST', 'centrifugo'),
    'port' => env('CENTRIFUGO_PORT', 8000),
    'api_key' => env('CENTRIFUGO_API_KEY', 'centrifugo_api_key'),
    'secret' => env('CENTRIFUGO_SECRET', 'centrifugo_secret'),
    'api_path' => env('CENTRIFUGO_API_PATH', '/api'),
    'namespace' => env('CENTRIFUGO_NAMESPACE', 'donations'),
    'ssl' => [
        'verify_peer' => env('CENTRIFUGO_VERIFY_PEER', true),
        'verify_peer_name' => env('CENTRIFUGO_VERIFY_PEER_NAME', true),
    ],
]; 