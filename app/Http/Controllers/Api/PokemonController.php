<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use Illuminate\Http\Request;

class PokemonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Pokemon::with(['types', 'abilities']);

        // Filtro por nome
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filtro por tipo
        if ($request->has('type')) {
            $query->whereHas('types', function ($q) use ($request) {
                $q->where('name', $request->type);
            });
        }

        // Filtro por habilidade
        if ($request->has('ability')) {
            $query->whereHas('abilities', function ($q) use ($request) {
                $q->where('name', $request->ability);
            });
        }

        // Paginação
        $perPage = $request->get('per_page', 15);
        $pokemon = $query->paginate($perPage);

        return response()->json($pokemon);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pokemon = Pokemon::with(['types', 'abilities'])->findOrFail($id);
        return response()->json($pokemon);
    }

    /**
     * Display pokemon by pokemon_id (from PokéAPI)
     *
     * @param  int  $pokemonId
     * @return \Illuminate\Http\Response
     */
    public function showByPokemonId($pokemonId)
    {
        $pokemon = Pokemon::with(['types', 'abilities'])
                         ->where('pokemon_id', $pokemonId)
                         ->firstOrFail();
        return response()->json($pokemon);
    }
}
