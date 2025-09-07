<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pokemon;
use App\Models\Type;
use App\Models\Ability;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class PopulatePokemonData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pokemon:populate {--limit=50 : Number of pokemon to fetch} {--offset=0 : Starting offset} {--clear : Clear existing data before populating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate database with Pokemon data from PokéAPI';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $offset = $this->option('offset');
        $clear = $this->option('clear');

        $this->info("Starting Pokemon data population...");
        $this->info("Limit: {$limit}, Offset: {$offset}");

        if ($clear) {
            $this->warn('Clearing existing data...');
            $this->clearExistingData();
        }

        try {
            // Buscar lista de pokémons
            $this->info('Fetching Pokemon list from PokéAPI...');
            $response = Http::get("https://pokeapi.co/api/v2/pokemon?limit={$limit}&offset={$offset}");
            
            if (!$response->successful()) {
                $this->error('Failed to fetch Pokemon list from PokéAPI');
                return 1;
            }

            $pokemonList = $response->json();
            $totalPokemon = count($pokemonList['results']);

            $this->info("Found {$totalPokemon} Pokemon to process");

            // Criar barra de progresso
            $progressBar = $this->output->createProgressBar($totalPokemon);
            $progressBar->start();

            $processed = 0;
            $errors = 0;

            foreach ($pokemonList['results'] as $pokemonData) {
                try {
                    $this->processPokemon($pokemonData['url']);
                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("\nError processing {$pokemonData['name']}: " . $e->getMessage());
                }
                
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            // Mostrar estatísticas
            $this->showStatistics($processed, $errors);

            return 0;

        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return 1;
        }
    }

    private function processPokemon($url)
    {
        $response = Http::get($url);
        
        if (!$response->successful()) {
            throw new \Exception("Failed to fetch Pokemon data from: {$url}");
        }

        $pokemonData = $response->json();

        DB::transaction(function () use ($pokemonData) {
            // Processar stats
            $stats = $this->processStats($pokemonData['stats']);
            
            // Criar ou atualizar o pokémon
            $pokemon = Pokemon::updateOrCreate(
                ['pokemon_id' => $pokemonData['id']],
                [
                    'name' => $pokemonData['name'],
                    'height' => $pokemonData['height'],
                    'weight' => $pokemonData['weight'],
                    'base_experience' => $pokemonData['base_experience'],
                    'order' => $pokemonData['order'],
                    'is_default' => $pokemonData['is_default'],
                    'sprite_url' => $pokemonData['sprites']['front_default'] ?? null,
                    'hp' => $stats['hp'],
                    'attack' => $stats['attack'],
                    'defense' => $stats['defense'],
                    'special_attack' => $stats['special_attack'],
                    'special_defense' => $stats['special_defense'],
                    'speed' => $stats['speed'],
                    'total_stats' => $stats['total'],
                ]
            );

            // Processar tipos
            $this->processTypes($pokemon, $pokemonData['types']);

            // Processar habilidades
            $this->processAbilities($pokemon, $pokemonData['abilities']);
        });
    }

    private function processTypes($pokemon, $types)
    {
        foreach ($types as $typeData) {
            $type = Type::firstOrCreate(
                ['name' => $typeData['type']['name']],
                ['url' => $typeData['type']['url']]
            );

            $pokemon->types()->syncWithoutDetaching([
                $type->id => ['slot' => $typeData['slot']]
            ]);
        }
    }

    private function processAbilities($pokemon, $abilities)
    {
        foreach ($abilities as $abilityData) {
            $ability = Ability::firstOrCreate(
                ['name' => $abilityData['ability']['name']],
                [
                    'url' => $abilityData['ability']['url'],
                    'is_hidden' => $abilityData['is_hidden']
                ]
            );

            $pokemon->abilities()->syncWithoutDetaching([
                $ability->id => [
                    'is_hidden' => $abilityData['is_hidden'],
                    'slot' => $abilityData['slot']
                ]
            ]);
        }
    }

    private function processStats($stats)
    {
        $processedStats = [
            'hp' => 0,
            'attack' => 0,
            'defense' => 0,
            'special_attack' => 0,
            'special_defense' => 0,
            'speed' => 0,
            'total' => 0
        ];

        foreach ($stats as $stat) {
            $statName = $stat['stat']['name'];
            $baseStat = $stat['base_stat'];

            switch ($statName) {
                case 'hp':
                    $processedStats['hp'] = $baseStat;
                    break;
                case 'attack':
                    $processedStats['attack'] = $baseStat;
                    break;
                case 'defense':
                    $processedStats['defense'] = $baseStat;
                    break;
                case 'special-attack':
                    $processedStats['special_attack'] = $baseStat;
                    break;
                case 'special-defense':
                    $processedStats['special_defense'] = $baseStat;
                    break;
                case 'speed':
                    $processedStats['speed'] = $baseStat;
                    break;
            }
        }

        $processedStats['total'] = array_sum([
            $processedStats['hp'],
            $processedStats['attack'],
            $processedStats['defense'],
            $processedStats['special_attack'],
            $processedStats['special_defense'],
            $processedStats['speed']
        ]);

        return $processedStats;
    }

    private function clearExistingData()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        Pokemon::truncate();
        Type::truncate();
        Ability::truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info('Existing data cleared successfully');
    }

    private function showStatistics($processed, $errors)
    {
        $this->newLine();
        $this->info('=== POPULATION COMPLETE ===');
        $this->info("✅ Successfully processed: {$processed} Pokemon");
        
        if ($errors > 0) {
            $this->warn("⚠️  Errors encountered: {$errors}");
        }

        // Mostrar estatísticas do banco
        $pokemonCount = Pokemon::count();
        $typeCount = Type::count();
        $abilityCount = Ability::count();

        $this->newLine();
        $this->info('=== DATABASE STATISTICS ===');
        $this->info("📊 Total Pokemon: {$pokemonCount}");
        $this->info("📊 Total Types: {$typeCount}");
        $this->info("📊 Total Abilities: {$abilityCount}");

        // Mostrar tipos mais comuns
        $this->newLine();
        $this->info('=== TOP TYPES ===');
        $topTypes = Type::withCount('pokemon')
            ->orderBy('pokemon_count', 'desc')
            ->limit(5)
            ->get();

        foreach ($topTypes as $type) {
            $this->info("🔥 {$type->name}: {$type->pokemon_count} Pokemon");
        }
    }
}
