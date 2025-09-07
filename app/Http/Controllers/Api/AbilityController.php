<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ability;
use Illuminate\Http\Request;

class AbilityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Ability::withCount('pokemon');

        // Filtro por nome
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filtro por tipo de habilidade
        if ($request->has('is_hidden')) {
            $query->where('is_hidden', $request->boolean('is_hidden'));
        }

        $abilities = $query->get();
        return response()->json($abilities);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $ability = Ability::with(['pokemon' => function ($query) {
            $query->limit(10); // Limitar a 10 pokémons por habilidade
        }])->findOrFail($id);
        
        return response()->json($ability);
    }

    /**
     * Get pokemon by ability
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function pokemon($id)
    {
        $ability = Ability::findOrFail($id);
        $pokemon = $ability->pokemon()->with(['types', 'abilities'])->paginate(15);
        
        return response()->json($pokemon);
    }
}
