<?php

return [
    'url' => env('PARSER_URL'),
    'node' => env('PARSER_NODE', 'node'),
    'script' => env('PARSER_SCRIPT', base_path('parser/yandex-parser.js')),
    'timeout' => (int) env('PARSER_TIMEOUT', 300),
    'sync_enabled' => env('PARSER_SYNC_ENABLED') !== null
        ? filter_var(env('PARSER_SYNC_ENABLED'), FILTER_VALIDATE_BOOLEAN)
        : env('APP_ENV') !== 'production',
];
