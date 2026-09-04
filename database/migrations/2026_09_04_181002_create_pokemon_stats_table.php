<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pokemon_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pokemon_id');

            // PokeAPI slug, stored as-is: "hp", "attack", "special-attack"...
            // Keeping stats as rows means a new stat upstream needs no
            // migration, only a new case in the PokemonMetric enum.
            $table->string('stat', 32);

            $table->unsignedSmallInteger('base_stat');
            $table->unsignedTinyInteger('effort');
            $table->timestamps();

            $table->foreign('pokemon_id')
                ->references('id')
                ->on('pokemons')
                ->cascadeOnDelete();

            // Upsert target: one row per (pokemon, stat).
            $table->unique(['pokemon_id', 'stat']);

            // Initial ranking index. A later migration extends it with
            // pokemon_id to cover the stable pagination tie-breaker too.
            $table->index(['stat', 'base_stat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokemon_stats');
    }
};
