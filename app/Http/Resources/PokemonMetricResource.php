<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PokemonMetricResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fields = array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) $request->input('fields', 'name,value'))
        )));

        $metric = (string) $request->input('metric', 'hp');
        $payload = [];

        if (in_array('name', $fields, true)) {
            $payload['name'] = $this->name;
        }

        if (in_array('value', $fields, true)) {
            $payload['value'] = $this->{$metric};
        }

        return $payload;
    }
}
