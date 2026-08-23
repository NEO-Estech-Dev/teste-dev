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
        Schema::create('games_indices', function (Blueprint $table) {
            $table->id();
            $table->integer('game_index');
            $table->string('version_name');
            $table->unsignedInteger('pokemon_id');
            $table->foreign('pokemon_id')->references('pokemon_id')->on('pokemons')->cascadeOnDelete();
            $table->unique(['pokemon_id', 'version_name']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games_indices');
    }
};
