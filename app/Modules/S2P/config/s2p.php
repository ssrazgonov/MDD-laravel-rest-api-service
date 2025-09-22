<?php

return [
    'url' => env('S2P_URL', 'demo.mars-t.ru'),
    'http_schema' => env('S2P_HTTP_SCHEMA', 'https'),
    'client_id' => env('S2P_CLIENT_ID', null),
    'client_secret' => env('S2P_CLIENT_SECRET', null),
    'grant_type' => env('S2P_GRANT_TYPE', null),
];
