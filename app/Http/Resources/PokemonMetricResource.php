<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\PokemonField;
use App\Models\Pokemon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Pokemon $resource
 */
final class PokemonMetricResource extends JsonResource
{
    /**
     * @param  list<PokemonField>  $fields
     */
    public function __construct(Pokemon $resource, private readonly array $fields)
    {
        parent::__construct($resource);
    }

    /**
     * Projects only the requested fields, in the requested order.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = [];

        foreach ($this->fields as $field) {
            $payload[$field->value] = match ($field) {
                PokemonField::Types => $this->resource->types->pluck('name')->all(),
                PokemonField::Value => (int) $this->resource->getAttribute('value'),
                PokemonField::BaseExperience => $this->resource->base_experience !== null
                    ? (int) $this->resource->base_experience
                    : null,
                default => $this->resource->getAttribute($field->value),
            };
        }

        return $payload;
    }
}
