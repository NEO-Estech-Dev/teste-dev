<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('pokemons', function (Blueprint $table) {
            $table->index(['hp', 'pokeapi_id']);
            $table->index(['attack', 'pokeapi_id']);
            $table->index(['defense', 'pokeapi_id']);
            $table->index(['special_attack', 'pokeapi_id']);
            $table->index(['special_defense', 'pokeapi_id']);
            $table->index(['speed', 'pokeapi_id']);
            $table->index(['height', 'pokeapi_id']);
            $table->index(['weight', 'pokeapi_id']);
            $table->index(['base_experience', 'pokeapi_id']);
        });
    }

    public function down(): void
    {
        Schema::table('pokemons', function (Blueprint $table) {
            $table->dropIndex(['hp', 'pokeapi_id']);
            $table->dropIndex(['attack', 'pokeapi_id']);
            $table->dropIndex(['defense', 'pokeapi_id']);
            $table->dropIndex(['special_attack', 'pokeapi_id']);
            $table->dropIndex(['special_defense', 'pokeapi_id']);
            $table->dropIndex(['speed', 'pokeapi_id']);
            $table->dropIndex(['height', 'pokeapi_id']);
            $table->dropIndex(['weight', 'pokeapi_id']);
            $table->dropIndex(['base_experience', 'pokeapi_id']);
        });
    }
};
