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
        Schema::create('pokemons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pokemon_id')->unique();
            $table->string('name');
            $table->integer('height');
            $table->integer('weight');
            $table->integer('order');
            $table->integer('base_experience')->nullable();
            $table->string('specie');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('pokemons');
    }
};
