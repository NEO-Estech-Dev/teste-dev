<?php

namespace Tests\Unit;

use App\Enums\PokemonMetric;
use PHPUnit\Framework\TestCase;

class PokemonMetricTest extends TestCase
{
    public function test_exposes_supported_metric_values(): void
    {
        $this->assertSame([
            'hp',
            'attack',
            'defense',
            'special_attack',
            'special_defense',
            'speed',
        ], PokemonMetric::values());
    }
}
