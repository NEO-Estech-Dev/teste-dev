<?php

namespace App\Console\Commands;

use App\Jobs\IngestPokemonChunkJob;
use App\Services\PokeApiIngestionService;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Throwable;

class PokeApiIngestCommand extends Command
{
    private const ASYNC_LOCK_KEY = 'pokeapi-ingestion';

    protected $signature = 'pokeapi:ingest
        {--limit= : Quantidade máxima de Pokémon a ingerir}
        {--offset=0 : Posição inicial na listagem da PokeAPI}
        {--chunk=50 : Tamanho de cada página buscada na PokeAPI}
        {--fresh : Limpa os dados Pokémon antes de ingerir}
        {--async : Despacha a ingestão para a fila}';

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

        if ((bool) $this->option('async')) {
            return $this->dispatchBatch($service, $limit, $offset, $chunk);
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

    private function dispatchBatch(PokeApiIngestionService $service, ?int $limit, int $offset, int $chunk): int
    {
        $lock = Cache::lock(self::ASYNC_LOCK_KEY, 3600);

        if (! $lock->get()) {
            $this->error('Já existe uma ingestão assíncrona da PokeAPI em andamento.');

            return self::FAILURE;
        }

        try {
            if ((bool) $this->option('fresh')) {
                $this->warn('Limpando dados Pokémon antes de despachar a ingestão assíncrona...');
                $service->clearDomainData();
            }

            $total = $limit ?? max(0, $service->pokemonCount() - $offset);
            $jobs = $this->buildChunkJobs($offset, $total, $chunk);

            if ($jobs === []) {
                $lock->release();
                $this->components->info('Nenhum Pokémon encontrado para ingestão assíncrona.');

                return self::SUCCESS;
            }

            $batch = Bus::batch($jobs)
                ->name('Ingestão PokeAPI')
                ->onConnection('redis')
                ->onQueue('pokeapi-ingestion')
                ->finally(fn (Batch $batch) => Cache::lock(self::ASYNC_LOCK_KEY)->forceRelease())
                ->dispatch();

            $this->components->info(sprintf(
                'Ingestão assíncrona despachada: batch_id=%s, jobs=%d, queue=pokeapi-ingestion.',
                $batch->id,
                count($jobs),
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $lock->release();

            throw $exception;
        }
    }

    /**
     * @return array<int, IngestPokemonChunkJob>
     */
    private function buildChunkJobs(int $offset, int $total, int $chunk): array
    {
        $jobs = [];
        $remaining = $total;
        $nextOffset = $offset;

        while ($remaining > 0) {
            $limit = min($chunk, $remaining);
            $jobs[] = new IngestPokemonChunkJob($nextOffset, $limit);
            $nextOffset += $limit;
            $remaining -= $limit;
        }

        return $jobs;
    }
}
