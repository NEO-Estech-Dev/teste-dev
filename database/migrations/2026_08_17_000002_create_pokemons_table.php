<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pokemons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pokeapi_id')->unique();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedSmallInteger('weight')->nullable();
            $table->unsignedSmallInteger('hp');
            $table->unsignedSmallInteger('attack');
            $table->unsignedSmallInteger('defense');
            $table->unsignedSmallInteger('special_attack');
            $table->unsignedSmallInteger('special_defense');
            $table->unsignedSmallInteger('speed');
            $table->string('sprite_url')->nullable();
            $table->timestamps();

            $table->index('hp');
            $table->index('attack');
            $table->index('defense');
            $table->index('special_attack');
            $table->index('special_defense');
            $table->index('speed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokemons');
    }
};
