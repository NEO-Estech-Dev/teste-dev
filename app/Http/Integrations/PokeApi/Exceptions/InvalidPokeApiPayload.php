<?php

declare(strict_types=1);

namespace App\Http\Integrations\PokeApi\Exceptions;

use RuntimeException;

final class InvalidPokeApiPayload extends RuntimeException
{
    public static function missingOrInvalid(string $field): self
    {
        return new self(sprintf('Invalid PokeAPI payload field: %s.', $field));
    }
}
