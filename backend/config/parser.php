<?php

return [
    'url' => env('PARSER_URL'),
    'node' => env('PARSER_NODE', 'node'),
    'script' => env('PARSER_SCRIPT', base_path('parser/yandex-parser.js')),
    'timeout' => (int) env('PARSER_TIMEOUT', 300),
];
