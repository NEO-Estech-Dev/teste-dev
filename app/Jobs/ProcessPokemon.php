<?php

namespace App\Jobs;

use App\Models\Pokemon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessPokemon implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $pokemonId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pokemon = Pokemon::find($this->pokemonId);

        if (!$pokemon) {
            Log::warning('Pokémon não encontrado para processamento.', [
                'pokemon_id' => $this->pokemonId,
            ]);

            return;
        }

        if ($pokemon->stats_processed_at !== null) {
            return;
        }

        $response = Http::timeout(60)->get($pokemon->external_url);

        if (!$response->successful()) {
            Log::error('Erro ao buscar dados do Pokémon.', [
                'pokemon_id' => $pokemon->id,
                'external_id' => $pokemon->external_id,
                'status' => $response->status(),
            ]);

            throw new \RuntimeException(
                "Falha ao consultar a PokeAPI. Status: {$response->status()}"
            );
        }

        $dataStats = $response->json();

        $this->createOrUpdateStatsPokemons($dataStats, $pokemon);

        // $secureTransaction = $this->createOrUpdateStatsPokemons($dataStats, $pokemon);

        // if (!$secureTransaction) {
        //     Log::error('Erro ao ataulizar os dados do Pokémon.', [
        //         'pokemon_id' => $pokemon->id,
        //         'external_id' => $pokemon->external_id,
        //         'status' => $response->status(),
        //     ]);
        // }
        
    }

    public function createOrUpdateStatsPokemons(Array $dataStats, Pokemon $pokemon)
    {
        return DB::transaction(function () use ($pokemon, $dataStats) {
            foreach ($dataStats['stats'] ?? [] as $stat) {
                $pokemon->stats()->updateOrCreate(
                    [
                        'stat_name' => $stat['stat']['name'],
                    ],
                    [
                        'base_stat' => $stat['base_stat'],
                        'effort' => $stat['effort'],
                    ]
                );
            }

            $pokemon->update([
                'height' => $dataStats['height'] ?? null,
                'weight' => $dataStats['weight'] ?? null,
                'stats_processed_at' => now(),
            ]);

            // return true;
        }, 2);
    }
}