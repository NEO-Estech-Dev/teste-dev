<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Services\PokemonIngestionService;

#[Signature('app:ingest-pokemons')]
#[Description('Importa os Pokémons da PokeAPI e envia seus processamentos para a fila')]
class IngestPokemons extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PokemonIngestionService $ingestionService)
    {
        $this->info('Iniciando ingestão dos Pokémon...');

        $ingestionService->handle();

        $this->info('Ingestão finalizada.');

        return self::SUCCESS;
    }
}
