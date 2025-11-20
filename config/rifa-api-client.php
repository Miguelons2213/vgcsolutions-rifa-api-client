<?php

return [
    'base_url' => env('RIFA_API_BASE_URL', 'https://example.com/api/rifas'),
    'public_key' => env('RIFA_API_KEY'),
    'secret' => env('RIFA_API_SECRET'),
    'signature_ttl' => env('RIFA_API_SIGNATURE_TTL', 60),
    'http' => [
        'timeout' => env('RIFA_API_TIMEOUT', 15),
        'connect_timeout' => env('RIFA_API_CONNECT_TIMEOUT', 5),
        'retry' => [
            'times' => env('RIFA_API_RETRY_TIMES', 1),
            'sleep' => env('RIFA_API_RETRY_SLEEP', 100),
        ],
    ],
    'default_headers' => [],
];
