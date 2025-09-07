<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Type;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Type::withCount('pokemon');

        // Filtro por nome
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $types = $query->get();
        return response()->json($types);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $type = Type::with(['pokemon' => function ($query) {
            $query->limit(10); // Limitar a 10 pokémons por tipo
        }])->findOrFail($id);
        
        return response()->json($type);
    }

    /**
     * Get pokemon by type
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function pokemon($id)
    {
        $type = Type::findOrFail($id);
        $pokemon = $type->pokemon()->with(['types', 'abilities'])->paginate(15);
        
        return response()->json($pokemon);
    }
}
