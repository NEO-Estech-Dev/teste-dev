<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Services\PokemonIntegrationService;

class IngestPokemonsCommand extends Command
{
    // O nome do comando que digitaremos no terminal
    protected $signature = 'app:ingest-pokemons {--limit=150 : Quantidade de Pokémons para importar}';
    protected $description = 'Ingere dados da PokeAPI e armazena no banco de dados';

    // O Laravel injeta o serviço automaticamente aqui graças ao Container de Injeção de Dependência
    public function handle(PokemonIntegrationService $service)
    {
        $this->info('Iniciando ingestão de dados da PokeAPI...');
        
        $limit = $this->option('limit');
        $this->warn("Buscando {$limit} Pokémons de forma concorrente. Isso pode levar alguns segundos...");
        
        // Chama a lógica de negócio isolada no Service
        $count = $service->sync($limit);

        $this->info("Sucesso! {$count} Pokémons foram importados/atualizados no banco de dados.");
    }
}
