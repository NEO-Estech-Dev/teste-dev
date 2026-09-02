<?php

namespace App\Http\Controllers;

use App\Services\PokemonIngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PokemonSyncController extends Controller
{
    public function store(Request $request, PokemonIngestService $ingestService): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1'],
        ]);

        $limit = $validated['limit'] ?? 151;
        $ingestService->ingest($limit);

        return response()->json([
            'message' => 'Sincronização de pokémons concluída com sucesso.',
            'limit' => $limit,
        ]);
    }
}
