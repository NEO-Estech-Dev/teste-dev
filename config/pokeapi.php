<?php

return [
    'base_url' => env('POKEAPI_BASE_URL', 'https://pokeapi.co/api/v2'),
    'timeout' => (int) env('POKEAPI_TIMEOUT', 15),
    'default_limit' => (int) env('POKEAPI_DEFAULT_LIMIT', 151),
    'concurrency' => (int) env('POKEAPI_CONCURRENCY', 10),
];
