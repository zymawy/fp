<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    */

    'default' => env('BROADCAST_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'null' => [
            'driver' => 'null',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'centrifugo' => [
            'driver' => 'centrifugo',
            'secret'  => env('CENTRIFUGO_SECRET', 'centrifugo_secret'),
            'apikey'  => env('CENTRIFUGO_API_KEY', 'centrifugo_api_key'),
            'api_path' => env('CENTRIFUGO_API_PATH', '/api'),
            'url'     => env('CENTRIFUGO_URL', 'http://centrifugo:8000'),
            'verify'  => env('CENTRIFUGO_VERIFY', false),
            'ssl_key' => env('CENTRIFUGO_SSL_KEY', null),
            'namespace' => env('CENTRIFUGO_NAMESPACE', 'donations'),
        ],

    ],

]; 