<?php

namespace App\Jobs;

use App\Services\PokeApiIngestionService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class IngestPokemonChunkJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $offset,
        public readonly int $limit,
    ) {
        $this->onConnection('redis');
        $this->onQueue('pokeapi-ingestion');
    }

    public function handle(PokeApiIngestionService $service): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $service->ingestChunk(
            offset: $this->offset,
            limit: $this->limit,
        );
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('pokeapi-ingestion'))->releaseAfter(10)->expireAfter(180),
            new RateLimited('pokeapi'),
        ];
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'pokeapi-ingestion',
            "offset:{$this->offset}",
            "limit:{$this->limit}",
        ];
    }
}
