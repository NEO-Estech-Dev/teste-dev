<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('species', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pokeapi_id')->unique();
            $table->string('name')->index();
            $table->unsignedSmallInteger('base_happiness')->nullable();
            $table->unsignedSmallInteger('capture_rate')->nullable();
            $table->boolean('is_baby')->default(false);
            $table->boolean('is_legendary')->default(false);
            $table->boolean('is_mythical')->default(false);
            $table->timestamps();
        });

        Schema::create('pokemons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pokeapi_id')->unique();
            $table->foreignId('species_id')->nullable()->constrained('species')->nullOnDelete();
            $table->string('name')->index();
            $table->unsignedSmallInteger('height');
            $table->unsignedSmallInteger('weight');
            $table->unsignedSmallInteger('base_experience')->nullable();
            $table->timestamps();
        });

        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pokeapi_id')->nullable()->unique();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('pokemon_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemons')->cascadeOnDelete();
            $table->foreignId('stat_id')->constrained('stats')->cascadeOnDelete();
            $table->unsignedSmallInteger('base_stat');
            $table->unsignedSmallInteger('effort')->default(0);
            $table->timestamps();

            $table->unique(['pokemon_id', 'stat_id']);
            $table->index(['stat_id', 'base_stat']);
        });

        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pokeapi_id')->nullable()->unique();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('pokemon_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemons')->cascadeOnDelete();
            $table->foreignId('type_id')->constrained('types')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot');
            $table->timestamps();

            $table->unique(['pokemon_id', 'type_id']);
            $table->unique(['pokemon_id', 'slot']);
        });

        Schema::create('abilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pokeapi_id')->nullable()->unique();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('pokemon_ability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemons')->cascadeOnDelete();
            $table->foreignId('ability_id')->constrained('abilities')->cascadeOnDelete();
            $table->boolean('is_hidden')->default(false);
            $table->unsignedTinyInteger('slot');
            $table->timestamps();

            $table->unique(['pokemon_id', 'ability_id']);
            $table->unique(['pokemon_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokemon_ability');
        Schema::dropIfExists('abilities');
        Schema::dropIfExists('pokemon_type');
        Schema::dropIfExists('types');
        Schema::dropIfExists('pokemon_stats');
        Schema::dropIfExists('stats');
        Schema::dropIfExists('pokemons');
        Schema::dropIfExists('species');
    }
};
