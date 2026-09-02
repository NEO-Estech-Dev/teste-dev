<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('pokemon_stats', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pokemon_id')->constrained('pokemons')->cascadeOnDelete();
        $table->string('stat_name');
        $table->unsignedInteger('base_value');
        $table->timestamps();

        $table->unique(['pokemon_id', 'stat_name']);
        $table->index(['stat_name', 'base_value']);
    });
}

public function down(): void
{
    Schema::dropIfExists('pokemon_stats');
}
};
