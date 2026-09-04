<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PokeApi\PokeApiClient;
use App\Services\Pokemon\PokemonIngestService;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'pokemon:ingest')]
final class IngestPokemonCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'pokemon:ingest
                            {--limit= : How many Pokemon to ingest (default: the whole Pokedex)}
                            {--offset=0 : Where to start in the Pokedex}
                            {--chunk= : Records written per database transaction}
                            {--concurrency= : Detail requests issued in parallel}
                            {--fresh : Wipe the ingested tables before importing}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Ingest Pokemon data from the public PokeAPI into the database';

    public function handle(PokeApiClient $client, PokemonIngestService $ingest): int
    {
        if ($this->option('fresh') && ! $this->confirmToProceed(
            'This wipes every ingested Pokemon row',
            fn (): bool => ! $this->laravel->environment('local', 'testing'),
        )) {
            return self::FAILURE;
        }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $offset = (int) $this->option('offset');
        $chunkSize = (int) ($this->option('chunk') ?? config('pokeapi.chunk_size'));
        $concurrency = (int) ($this->option('concurrency') ?? config('pokeapi.concurrency'));

        $this->components->info('Fetching the Pokedex index from the PokeAPI...');

        $urls = $client->listResourceUrls($limit, $offset);

        if ($urls === []) {
            $this->components->warn('Nothing to ingest.');

            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->components->info('Clearing previously ingested data...');
            $ingest->truncate();
        }

        $this->components->info(sprintf(
            'Ingesting %d Pokemon (%d per transaction, %d requests in parallel).',
            count($urls),
            $chunkSize,
            $concurrency,
        ));

        $startedAt = microtime(true);
        $ingested = 0;
        $failed = [];

        $bar = $this->output->createProgressBar(count($urls));
        $bar->start();

        foreach (array_chunk($urls, max($chunkSize, 1)) as $chunk) {
            $result = $client->fetchMany($chunk, $concurrency);

            $ingest->storeChunk($result['pokemon']);

            $ingested += count($result['pokemon']);
            $failed = [...$failed, ...$result['failed']];

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine(2);

        Cache::tags([(string) config('metrics.cache_tag')])->flush();

        $this->components->info(sprintf(
            'Ingested %d Pokemon in %.1fs.',
            $ingested,
            microtime(true) - $startedAt,
        ));

        if ($failed !== []) {
            Log::warning('PokeAPI ingestion finished with failures.', [
                'failed_count' => count($failed),
                'failed' => array_slice($failed, 0, 50),
            ]);

            $this->components->error(sprintf('%d resource(s) could not be ingested:', count($failed)));

            foreach (array_slice($failed, 0, 10) as $url) {
                $this->line('  - '.$url);
            }

            if (count($failed) > 10) {
                $this->line(sprintf('  ... and %d more (see the application log).', count($failed) - 10));
            }

            // A partial ingestion must not look like a success to CI or to a
            // scheduler retrying the command.
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
