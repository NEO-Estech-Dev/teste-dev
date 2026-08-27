<?php

namespace App\Http\Controllers;

use App\Exception\CustomException;
use App\Http\Requests\IndexMetricPokemonsRequest;
use App\Services\Pokemon\IndexPokemonService;
use Illuminate\Http\Response;

class PokemonController extends Controller 
{
    public function indexMetrics(IndexMetricPokemonsRequest $request, IndexPokemonService $service)
    {
        try {

            $validated = $request->validated();

            $response = $service->execute(
                page:   $validated['page'] ?? 1,
                limit:  $validated['limit'] ?? 10,
                metric: $validated['metric'] ?? 'name',
                fields: $validated['fields'] ?? null,
                order:  $validated['order'] ?? 'asc',
            );

            return response()->json(
                $response,
                Response::HTTP_OK
            );
        } catch (\Throwable $error) {
            CustomException::exception($error);
        }
    }
}