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
            $table->integer('pokeapi_id')->unique(); // ID original da PokeAPI
            $table->string('name')->unique();
            $table->integer('hp')->index();
            $table->integer('attack')->index();
            $table->integer('defense')->index();
            $table->integer('special_attack')->index();
            $table->integer('special_defense')->index();
            $table->integer('speed')->index();
            $table->integer('weight');
            $table->integer('height');
            
            $table->timestamps();
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
