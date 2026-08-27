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
        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->integer('effort');
            $table->integer('base_stat');
            $table->string('stat_name');
            $table->unsignedInteger('pokemon_id');
            $table->foreign('pokemon_id')->references('pokemon_id')->on('pokemons')->cascadeOnDelete();
            $table->unique(['pokemon_id', 'stat_name']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats');
    }
};
