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
        $table->unsignedInteger('pokeapi_id')->unique();
        $table->string('name');
        $table->unsignedInteger('height')->nullable();
        $table->unsignedInteger('weight')->nullable();
        $table->unsignedInteger('base_experience')->nullable();
        $table->string('sprite_url')->nullable();
        $table->timestamps();

        $table->index('name');
    });
}

public function down(): void
{
    Schema::dropIfExists('pokemons');
}
};
