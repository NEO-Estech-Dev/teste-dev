<?php

namespace App\Services;

use App\Models\Pokemon;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PokemonIntegrationService
{
    /**
     * Sincroniza os dados da PokeAPI com o nosso banco de dados.
     */
    public function sync(int $limit = 150): int
    {
        // 1. Busca a lista principal (vem apenas nome e a URL com os detalhes)
        $response = Http::get("https://pokeapi.co/api/v2/pokemon", [
            'limit' => $limit
        ]);
        
        $results = $response->json('results');
        if (!$results) return 0;

        // 2. Escalabilidade: Divide as requisições em lotes (chunks) para não sobrecarregar a API e memória
        $chunks = array_chunk($results, 50);
        $dataToSave = [];

        foreach ($chunks as $chunk) {
            // Busca os detalhes do lote concorrentemente
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                return collect($chunk)->map(function ($pokemon) use ($pool) {
                    return $pool->get($pokemon['url']);
                })->toArray();
            });

            // 3. Formatação dos dados do lote
            foreach ($responses as $res) {
                if ($res->ok()) {
                    $data = $res->json();
                    
                    $dataToSave[] = [
                        'pokeapi_id'      => $data['id'],
                        'name'            => $data['name'],
                        'hp'              => $this->getStat($data['stats'], 'hp'),
                        'attack'          => $this->getStat($data['stats'], 'attack'),
                        'defense'         => $this->getStat($data['stats'], 'defense'),
                        'special_attack'  => $this->getStat($data['stats'], 'special-attack'),
                        'special_defense' => $this->getStat($data['stats'], 'special-defense'),
                        'speed'           => $this->getStat($data['stats'], 'speed'),
                        'weight'          => $data['weight'],
                        'height'          => $data['height'],
                    ];
                }
            }
        }

        if (empty($dataToSave)) {
            return 0;
        }

        // 4. Alta Performance no Banco: Insere todos de uma vez (Upsert)
        Pokemon::upsert(
            $dataToSave,
            ['pokeapi_id'], // Qual coluna define se é um registro único
            ['name', 'hp', 'attack', 'defense', 'special_attack', 'special_defense', 'speed', 'weight', 'height'] // O que atualizar se já existir
        );

        // 5. Limpa o cache antigo para que a API retorne os dados novos na próxima requisição
        Cache::flush();

        return count($dataToSave);
    }

    /**
     * Método auxiliar para extrair o valor da métrica de dentro do array da PokeAPI
     */
    private function getStat(array $stats, string $statName): int
    {
        foreach ($stats as $stat) {
            if ($stat['stat']['name'] === $statName) {
                return $stat['base_stat'];
            }
        }
        return 0;
    }
}