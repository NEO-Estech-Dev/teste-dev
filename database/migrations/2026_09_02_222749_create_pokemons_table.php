<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pokemons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pokeapi_id')->unique();
            $table->string('name');
            $table->unsignedInteger('height');
            $table->unsignedInteger('weight');
            $table->unsignedInteger('base_experience')->nullable();
            $table->unsignedInteger('hp');
            $table->unsignedInteger('attack');
            $table->unsignedInteger('defense');
            $table->unsignedInteger('special_attack');
            $table->unsignedInteger('special_defense');
            $table->unsignedInteger('speed');
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
