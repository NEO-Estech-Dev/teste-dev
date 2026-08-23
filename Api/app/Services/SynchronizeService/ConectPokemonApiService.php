<?php

namespace App\Services\SynchronizeService;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConectPokemonApiService 
{
    public function execute(string $rota)
    {
        $response = Http::withHeaders(['Accept' => 'application/json'])
            ->timeout(160)
            ->get($rota);

        if ($response->failed()) {
            throw new Exception(
                "Erro ao se conectar com " . $rota . " (status: " . $response->status() . ")",
                $response->status()
            );
        }

        $data = $response->json();

        if (is_null($data)) {
            throw new Exception("Resposta inválida (não é JSON) de " . $rota, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $data;
    }

    public function executePool(array $rotas): array
    {
        $lotes = 20;
        $resultados = [];

        foreach (array_chunk($rotas, $lotes) as $chunk) {

            $responses = Http::pool(fn ($pool) => array_map(
                fn ($rota) => $pool->withHeaders(['Accept' => 'application/json'])
                    ->timeout(160)
                    ->get($rota),
                $chunk
            ));
            
            foreach ($responses as $index => $response) {
            
                if ($response instanceof \Throwable || $response->failed()) {
                    Log::warning("Falha ao buscar rota do executePool: " . $rotas[$index]);
                    continue;
                }
                    
                $data = $response->json();          

                if (is_null($data)) {
                    continue; 
                }

                $resultados[] = [
                    'name' => $response['name'] ?? null,
                    'height' => $response['height'] ?? null,
                    'weight' => $response['weight'] ?? null,
                    'order' => $response['order'] ?? null,
                    'specie' => $response['species']['name'] ?? null,
                    'pokemon_id' => $response['id'] ?? null,
                    'base_experience' => $response['base_experience'] ?? null,
                    'game_indices' => $response['game_indices'] ?? null,
                    'stats' => $response['stats'] ?? null,
                ];

                unset($data, $response);
            }
            unset($response);
        }

        return $resultados;
    }
}