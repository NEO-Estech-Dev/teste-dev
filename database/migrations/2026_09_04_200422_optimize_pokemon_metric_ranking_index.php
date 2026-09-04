<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pokemon_stats', function (Blueprint $table) {
            $table->index(
                ['stat', 'base_stat', 'pokemon_id'],
                'pokemon_stats_metric_ranking_index',
            );
            $table->dropIndex('pokemon_stats_stat_base_stat_index');
        });
    }

    public function down(): void
    {
        Schema::table('pokemon_stats', function (Blueprint $table) {
            $table->index(['stat', 'base_stat']);
            $table->dropIndex('pokemon_stats_metric_ranking_index');
        });
    }
};
