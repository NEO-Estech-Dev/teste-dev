<?php

namespace App\Console\Commands;

use App\Services\PokeApiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('pokemon:ingest {--limit=0 : Quantidade máxima; zero processa todos} {--batch=25 : Registros persistidos por lote} {--concurrency=5 : Requisições simultâneas de detalhes} {--start=0 : Offset da PokeAPI para retomar uma execução}')]
#[Description('Importa Pokémon e suas estatísticas básicas da PokeAPI')]
class IngestPokemon extends Command
{
    public function handle(PokeApiService $pokeApi): int
    {
        $limit = (int) $this->option('limit');
        $batchSize = (int) $this->option('batch');
        $concurrency = (int) $this->option('concurrency');
        $start = (int) $this->option('start');

        if ($limit < 0 || $start < 0 || $batchSize < 1 || $batchSize > 50 || $concurrency < 1 || $concurrency > 10) {
            $this->error('Use limit/start >= 0, batch entre 1 e 50 e concurrency entre 1 e 10.');

            return self::INVALID;
        }

        try {
            $available = max(0, $pokeApi->count() - $start);
            $total = $limit === 0 ? $available : min($limit, $available);
            $progress = $this->output->createProgressBar($total);
            $progress->start();

            for ($processed = 0; $processed < $total; $processed += $batchSize) {
                $size = min($batchSize, $total - $processed);
                $items = $pokeApi->page($start + $processed, $size);
                $rows = $pokeApi->details($items, $concurrency);
                $now = now();

                foreach ($rows as &$row) {
                    $row['types'] = json_encode($row['types'], JSON_THROW_ON_ERROR);
                    $row['created_at'] = $now;
                    $row['updated_at'] = $now;
                }
                unset($row);

                DB::table('pokemon')->upsert(
                    $rows,
                    ['pokeapi_id'],
                    ['name', 'height', 'weight', 'base_experience', 'types', 'hp', 'attack', 'defense', 'special_attack', 'special_defense', 'speed', 'updated_at'],
                );

                $progress->advance(count($rows));

                // Detail payloads are large; release each batch before requesting the next one.
                unset($items, $rows);
                gc_collect_cycles();
            }

            $progress->finish();
            $this->newLine(2);
            $this->info("Ingestão concluída: {$total} Pokémon processados.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->newLine();
            report($exception);
            $this->error('Ingestão interrompida: '.$exception->getMessage());
            $this->line('Os lotes anteriores foram preservados; execute novamente para continuar com seguranca.');

            return self::FAILURE;
        }
    }
}
