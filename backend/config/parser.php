<?php

$parserUrl = env('PARSER_URL');

if (! filled($parserUrl)) {
    $host = env('PARSER_PRIVATE_HOST', env('PARSER_HOST'));
    $port = env('PARSER_PRIVATE_PORT', env('PARSER_PORT', 3000));

    if (filled($host)) {
        $parserUrl = 'http://'.trim((string) $host, '/').':'.$port;
    } elseif (filled(env('PARSER_SERVICE'))) {
        $parserUrl = 'http://'.env('PARSER_SERVICE').'.railway.internal:'.$port;
    }
}

return [
    'url' => filled($parserUrl) ? $parserUrl : null,
    'node' => env('PARSER_NODE', 'node'),
    'script' => env('PARSER_SCRIPT', base_path('parser/yandex-parser.js')),
    'timeout' => (int) env('PARSER_TIMEOUT', 300),
    'sync_enabled' => env('PARSER_SYNC_ENABLED') !== null
        ? filter_var(env('PARSER_SYNC_ENABLED'), FILTER_VALIDATE_BOOLEAN)
        : env('APP_ENV') !== 'production',
];
