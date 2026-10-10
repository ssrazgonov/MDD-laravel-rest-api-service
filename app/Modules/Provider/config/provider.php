<?php

return [
    'url' => env('PROVIDER_URL', 'api.example.com'),
    'http_schema' => env('PROVIDER_HTTP_SCHEMA', 'https'),
    'client_id' => env('PROVIDER_CLIENT_ID', null),
    'client_secret' => env('PROVIDER_CLIENT_SECRET', null),
    'grant_type' => env('PROVIDER_GRANT_TYPE', null),
];
