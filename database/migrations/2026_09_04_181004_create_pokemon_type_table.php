<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pokemon_type', function (Blueprint $table) {
            $table->unsignedInteger('pokemon_id');
            $table->unsignedSmallInteger('type_id');
            $table->unsignedTinyInteger('slot');

            // Composite primary key doubles as the upsert target.
            $table->primary(['pokemon_id', 'type_id']);

            // Reverse lookup for the ?type= filter.
            $table->index('type_id');

            $table->foreign('pokemon_id')
                ->references('id')
                ->on('pokemons')
                ->cascadeOnDelete();

            $table->foreign('type_id')
                ->references('id')
                ->on('types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokemon_type');
    }
};
