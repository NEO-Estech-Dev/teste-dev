<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Pokemon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

/** @mixin Pokemon */
final class RankedPokemonResource extends JsonResource
{
    private const string INVALID_RESOURCE = 'Ranked Pokemon resources require a Pokemon model.';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Pokemon) {
            throw new LogicException(self::INVALID_RESOURCE);
        }

        return $this->resource->attributesToArray();
    }
}
