<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Metrics authentication
    |--------------------------------------------------------------------------
    |
    | The ranking endpoint ships behind Sanctum. This flag allows a local
    | evaluator to inspect it without changing the route definition.
    |
    */

    'require_authentication' => (bool) env('METRICS_REQUIRE_AUTH', true),

    'cache_tag' => 'pokemon_metrics',

    'cache_ttl' => (int) env('METRICS_CACHE_TTL', 300),

];
