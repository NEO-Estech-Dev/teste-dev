<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    /**
     * Convenção de nomenclatura:
     *
     * As migrations de criação de recursos seguem o prefixo "create_*",
     * enquanto alterações posteriores em recursos existentes seguem o
     * prefixo "update_*".
     *
     * Essa é uma pequena convenção que costumo utilizar em meus projetos
     * pessoais para facilitar a localização e a identificação das alterações
     * durante uma eventual manutenção.
     *
     * Entendo que cada equipe pode adotar sua própria convenção de
     * nomenclatura e, em um ambiente de trabalho, sigo o padrão definido
     * pelo time e pelo projeto.
    */

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pokemons_stats', function (Blueprint $table) {
            $table->index(
                ['stat_name', 'base_stat'],
                'pokemons_stats_stat_name_base_stat_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pokemons_stats', function (Blueprint $table) {
            $table->dropIndex('pokemons_stats_stat_name_base_stat_index');
        });
    }
};
