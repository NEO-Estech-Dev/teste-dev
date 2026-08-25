<?php

namespace App\Console\Commands;

use App\Services\PokeApiIngestionService;
use Illuminate\Console\Command;

class PokeApiIngestCommand extends Command
{
    protected $signature = 'pokeapi:ingest
        {--limit= : Quantidade máxima de Pokémon a ingerir}
        {--offset=0 : Posição inicial na listagem da PokeAPI}
        {--chunk=50 : Tamanho de cada página buscada na PokeAPI}
        {--fresh : Limpa os dados Pokémon antes de ingerir}';

    protected $description = 'Ingere Pokémon, espécies, métricas, tipos e habilidades da PokeAPI.';

    public function handle(PokeApiIngestionService $service): int
    {
        $limit = $this->option('limit') === null ? null : (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $chunk = (int) $this->option('chunk');

        if ($limit !== null && $limit < 1) {
            $this->error('A opção --limit deve ser maior que zero.');

            return self::FAILURE;
        }

        if ($offset < 0) {
            $this->error('A opção --offset não pode ser negativa.');

            return self::FAILURE;
        }

        if ($chunk < 1 || $chunk > 200) {
            $this->error('A opção --chunk deve estar entre 1 e 200.');

            return self::FAILURE;
        }

        $this->info('Iniciando ingestão da PokeAPI...');

        $summary = $service->ingest(
            limit: $limit,
            offset: $offset,
            chunk: $chunk,
            fresh: (bool) $this->option('fresh'),
        );

        $this->components->info(sprintf(
            'Ingestão concluída: %d Pokémon processados em %d página(s).',
            $summary['processed'],
            $summary['pages'],
        ));

        return self::SUCCESS;
    }
}
