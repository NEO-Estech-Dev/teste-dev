<?php

namespace App\Console\Commands;

use App\Services\Pokemon\PokemonIngestionService;
use Illuminate\Console\Command;

class IngestPokemonsCommand extends Command
{
    protected $signature = 'pokemons:ingest
        {--limit= : Quantidade de Pokémon a importar}
        {--offset=0 : Offset na listagem da PokeAPI}
        {--fresh : Remove os dados existentes antes de importar}';

    protected $description = 'Consome a PokeAPI e persiste Pokémon e tipos no MySQL';

    public function handle(PokemonIngestionService $ingestion): int
    {
        $limit = (int) ($this->option('limit') ?: config('pokeapi.default_limit'));
        $offset = (int) $this->option('offset');
        $fresh = (bool) $this->option('fresh');

        $this->info("Iniciando ingestão de {$limit} Pokémon (offset {$offset})...");

        $bar = $this->output->createProgressBar($limit);
        $bar->start();

        $result = $ingestion->ingest(
            limit: $limit,
            offset: $offset,
            fresh: $fresh,
            onProgress: function () use ($bar) {
                $bar->advance();
            }
        );

        $bar->finish();
        $this->newLine(2);
        $this->info("Ingestão concluída: {$result['imported']} persistidos, {$result['failed']} falhas.");

        if ($result['errors'] !== []) {
            $this->warn('Itens com falha:');

            foreach ($result['errors'] as $error) {
                $this->line(" - {$error['name']}: {$error['error']}");
            }
        }

        if ($result['imported'] === 0 && $result['failed'] > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
