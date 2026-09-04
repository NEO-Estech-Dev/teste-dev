<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | PokeAPI
    |--------------------------------------------------------------------------
    |
    | Connection settings used by the ingestion command. The retry/timeout
    | values apply to every individual request made inside the HTTP pool.
    |
    */

    'base_url' => env('POKEAPI_BASE_URL', 'https://pokeapi.co/api/v2'),

    'timeout' => (int) env('POKEAPI_TIMEOUT', 15),

    'retry' => [
        'times' => (int) env('POKEAPI_RETRY_TIMES', 3),
        'sleep' => (int) env('POKEAPI_RETRY_SLEEP', 200),
    ],

    /*
    | Number of Pokemon fetched per pool, and how many of those requests run
    | concurrently. Defaults are conservative enough not to hammer the public
    | API while still ingesting the full dex in well under a minute.
    */

    'chunk_size' => (int) env('POKEAPI_CHUNK_SIZE', 200),

    'concurrency' => (int) env('POKEAPI_CONCURRENCY', 25),

];
