<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pokemons', function (Blueprint $table) {
            // The PokeAPI id is the natural key. Reusing it (instead of an
            // auto-increment surrogate) is what makes the ingestion a plain
            // idempotent upsert, with no lookup round-trip per record.
            $table->unsignedInteger('id')->primary();

            $table->string('name', 64)->unique();
            $table->unsignedSmallInteger('height');
            $table->unsignedMediumInteger('weight');
            $table->unsignedSmallInteger('base_experience')->nullable();
            $table->integer('order');
            $table->boolean('is_default')->default(true);
            $table->string('sprite_url')->nullable();

            // Sum of the six base stats, computed during ingestion so the
            // "strongest overall" ranking is an indexed ORDER BY instead of a
            // GROUP BY + SUM on every request.
            $table->unsignedSmallInteger('stats_total')->default(0);

            $table->timestamps();

            // One index per rankable column on this table.
            $table->index('height');
            $table->index('weight');
            $table->index('base_experience');
            $table->index('stats_total');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokemons');
    }
};
