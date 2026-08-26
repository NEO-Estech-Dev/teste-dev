<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pokemon_stats', function (Blueprint $table) {
            $table->index(['stat_id', 'base_stat', 'pokemon_id'], 'pokemon_stats_metric_ranking_index');
            $table->dropIndex(['stat_id', 'base_stat']);
        });

        Schema::table('pokemon_type', function (Blueprint $table) {
            $table->index(['type_id', 'pokemon_id'], 'pokemon_type_type_lookup_index');
        });

        Schema::table('pokemon_ability', function (Blueprint $table) {
            $table->index(['ability_id', 'pokemon_id'], 'pokemon_ability_ability_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('pokemon_ability', function (Blueprint $table) {
            $table->dropIndex('pokemon_ability_ability_lookup_index');
        });

        Schema::table('pokemon_type', function (Blueprint $table) {
            $table->dropIndex('pokemon_type_type_lookup_index');
        });

        Schema::table('pokemon_stats', function (Blueprint $table) {
            $table->index(['stat_id', 'base_stat']);
            $table->dropIndex('pokemon_stats_metric_ranking_index');
        });
    }
};
