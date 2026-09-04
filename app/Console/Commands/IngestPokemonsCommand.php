<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\UpsertPokemon;
use App\Http\Integrations\PokeApi\PokeApiConnector;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

final class IngestPokemonsCommand extends Command
{
    private const string INVALID_LIMIT = 'The --limit option must be an integer greater than zero.';

    private const string INVALID_OFFSET = 'The --offset option must be an integer greater than or equal to zero.';

    private const string INVALID_CHUNK = 'The --chunk option must be an integer between 1 and 200.';

    private const string UNKNOWN_OPTION = 'Unknown command option.';

    private const string BATCH_PROGRESS = 'Synchronized batch at offset %d: %d Pokemon(s).';

    private const string BATCH_FAILURE = 'Failed to synchronize batch at offset %d: %s';

    private const string SUMMARY = 'Requested: %s; synchronized: %d; initial offset: %d; chunk size: %d; duration: %.2fs.';

    protected $signature = 'pokemon:ingest
        {--limit= : Maximum number of Pokemons to import}
        {--offset=0 : Starting offset}
        {--chunk=50 : Number of Pokemons processed at once}';

    protected $description = 'Ingest Pokemons from PokeAPI';

    /**
     * @return self::SUCCESS|self::FAILURE|self::INVALID
     */
    public function handle(PokeApiConnector $pokeApi, UpsertPokemon $upsertPokemon): int
    {
        try {
            $options = $this->validatedOptions();
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        $startedAt = microtime(true);
        $cursor = $options['offset'];
        $remaining = $options['limit'];
        $synchronized = 0;
        $apiTotal = null;
        $pokemons = $pokeApi->pokemons();

        while ($remaining === null || $remaining > 0) {
            $pageSize = min($options['chunk'], $remaining ?? $options['chunk']);

            try {
                $page = $pokemons->list($pageSize, $cursor);
                $apiTotal ??= $page->total;

                if ($page->items->isEmpty()) {
                    break;
                }

                $details = $pokemons->details($page->items);
                $upsertPokemon->handle($details);
            } catch (Throwable $exception) {
                $this->error(sprintf(self::BATCH_FAILURE, $cursor, $exception->getMessage()));

                return self::FAILURE;
            }

            $batchSize = $page->items->count();
            $synchronized += $batchSize;
            $this->info(sprintf(self::BATCH_PROGRESS, $cursor, $batchSize));
            $cursor += $batchSize;

            if ($remaining !== null) {
                $remaining -= $batchSize;
            }

            if (! $page->hasNextPage) {
                break;
            }
        }

        $requested = $options['limit'] === null ? (string) ($apiTotal ?? 0) : (string) $options['limit'];
        $duration = microtime(true) - $startedAt;

        $this->info(sprintf(
            self::SUMMARY,
            $requested,
            $synchronized,
            $options['offset'],
            $options['chunk'],
            $duration,
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{limit: int|null, offset: int, chunk: int}
     */
    private function validatedOptions(): array
    {
        $limit = $this->integerOption('limit', nullable: true);
        $offset = $this->integerOption('offset') ?? throw new InvalidArgumentException(self::INVALID_OFFSET);
        $chunk = $this->integerOption('chunk') ?? throw new InvalidArgumentException(self::INVALID_CHUNK);

        if ($limit === null && $this->input->hasParameterOption('--limit')) {
            throw new InvalidArgumentException(self::INVALID_LIMIT);
        }

        if ($limit !== null && $limit < 1) {
            throw new InvalidArgumentException(self::INVALID_LIMIT);
        }

        if ($offset < 0) {
            throw new InvalidArgumentException(self::INVALID_OFFSET);
        }

        if ($chunk < 1 || $chunk > 200) {
            throw new InvalidArgumentException(self::INVALID_CHUNK);
        }

        return [
            'limit' => $limit,
            'offset' => $offset,
            'chunk' => $chunk,
        ];
    }

    private function integerOption(string $name, bool $nullable = false): ?int
    {
        $value = $this->option($name);

        if ($nullable && $value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value) || preg_match('/^\d+$/D', $value) !== 1) {
            throw new InvalidArgumentException($this->invalidOptionMessage($name));
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if (! is_int($integer)) {
            throw new InvalidArgumentException($this->invalidOptionMessage($name));
        }

        return $integer;
    }

    private function invalidOptionMessage(string $name): string
    {
        return match ($name) {
            'limit' => self::INVALID_LIMIT,
            'offset' => self::INVALID_OFFSET,
            'chunk' => self::INVALID_CHUNK,
            default => throw new InvalidArgumentException(self::UNKNOWN_OPTION),
        };
    }
}
