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
        Schema::create('pokemon', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pokeapi_id')->unique();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('height');
            $table->unsignedSmallInteger('weight');
            $table->unsignedSmallInteger('base_experience')->nullable();
            $table->json('types');
            $table->unsignedSmallInteger('hp');
            $table->unsignedSmallInteger('attack');
            $table->unsignedSmallInteger('defense');
            $table->unsignedSmallInteger('special_attack');
            $table->unsignedSmallInteger('special_defense');
            $table->unsignedSmallInteger('speed');
            $table->timestamps();

            // Each metric can be served directly from an index, with a stable id tie-breaker.
            foreach (['hp', 'attack', 'defense', 'special_attack', 'special_defense', 'speed'] as $metric) {
                $table->index([$metric, 'id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokemon');
    }
};
