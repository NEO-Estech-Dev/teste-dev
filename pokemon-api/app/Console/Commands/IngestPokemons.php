<?php

namespace App\Console\Commands;

use App\Services\PokemonIngestService;
use Illuminate\Console\Command;

class IngestPokemons extends Command
{
    protected $signature = 'pokemon:ingest {--limit=151}';

    protected $description = 'Consome a PokeAPI e persiste pokémons e suas estatísticas no banco de dados';

    public function handle(PokemonIngestService $ingestService): int
    {
        $limit = (int) $this->option('limit');

        $this->info("Buscando lista de {$limit} pokémons na PokeAPI...");

        try {
            $ingestService->ingest($limit);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Ingestão concluída com sucesso.');

        return self::SUCCESS;
    }
}
