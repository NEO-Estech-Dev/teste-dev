<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pokemon;
use App\Models\Type;
use App\Models\Ability;
use Illuminate\Support\Facades\Http;

class PokemonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Buscar os primeiros 50 pokémons da PokéAPI
        $response = Http::get('https://pokeapi.co/api/v2/pokemon?limit=50');
        $pokemonList = $response->json();

        foreach ($pokemonList['results'] as $pokemonData) {
            $this->seedPokemon($pokemonData['url']);
        }
    }

    private function seedPokemon($url)
    {
        $response = Http::get($url);
        $pokemonData = $response->json();

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
            ]
        );

        // Processar tipos
        foreach ($pokemonData['types'] as $typeData) {
            $type = Type::firstOrCreate(
                ['name' => $typeData['type']['name']],
                ['url' => $typeData['type']['url']]
            );

            $pokemon->types()->syncWithoutDetaching([
                $type->id => ['slot' => $typeData['slot']]
            ]);
        }

        // Processar habilidades
        foreach ($pokemonData['abilities'] as $abilityData) {
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

        echo "Pokémon {$pokemon->name} seeded successfully!\n";
    }
}
