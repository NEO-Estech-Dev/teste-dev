<?php

namespace App\Console\Commands;

use App\Models\Pokemon;
use App\Models\PokemonStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class IngestPokemons extends Command
{
    protected $signature = 'pokemon:ingest {--limit=151}';

    protected $description = 'Consome a PokeAPI e persiste pokémons e suas estatísticas no banco de dados';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $this->info("Buscando lista de {$limit} pokémons na PokeAPI...");

        $response = Http::get("https://pokeapi.co/api/v2/pokemon?limit={$limit}");

        if ($response->failed()) {
            $this->error('Falha ao consultar a PokeAPI.');
            return self::FAILURE;
        }

        $results = $response->json('results');

        $bar = $this->output->createProgressBar(count($results));
        $bar->start();

        foreach ($results as $item) {
            $detail = Http::get($item['url'])->json();

            $pokemon = Pokemon::updateOrCreate(
                ['pokeapi_id' => $detail['id']],
                [
                    'name' => $detail['name'],
                    'height' => $detail['height'],
                    'weight' => $detail['weight'],
                    'base_experience' => $detail['base_experience'],
                    'sprite_url' => $detail['sprites']['front_default'] ?? null,
                ]
            );

            foreach ($detail['stats'] as $stat) {
                PokemonStat::updateOrCreate(
                    [
                        'pokemon_id' => $pokemon->id,
                        'stat_name' => $stat['stat']['name'],
                    ],
                    [
                        'base_value' => $stat['base_stat'],
                    ]
                );
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Ingestão concluída com sucesso.');

        return self::SUCCESS;
    }
}