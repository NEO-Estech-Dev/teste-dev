<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\PokemonField;
use App\Enums\PokemonMetric;
use App\Enums\RankingOrder;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type;
use Illuminate\Support\ServiceProvider;

final class OpenApiServiceProvider extends ServiceProvider
{
    private const string RANKING_OPERATION_ID = 'getPokemonRanking';

    public function boot(): void
    {
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            foreach ($openApi->paths as $path) {
                foreach ($path->operations as $operation) {
                    if ($operation->operationId === self::RANKING_OPERATION_ID) {
                        $this->documentRankingResponse($operation);
                    }
                }
            }
        });
    }

    private function documentRankingResponse(Operation $operation): void
    {
        $response = $this->findResponse($operation, 200);
        $schema = $this->resolveSchema($response?->content['application/json'] ?? null);

        if (! $schema?->type instanceof ObjectType) {
            return;
        }

        $data = $schema->type->getProperty('data');

        if ($data instanceof ArrayType) {
            $itemSchema = $this->resolveSchema($data->items);

            if ($itemSchema) {
                $itemSchema->type = $this->rankingItemType();
            } else {
                $data->setItems($this->rankingItemType());
            }
        }

        $paginationLinks = $schema->type->getProperty('links');

        if ($paginationLinks instanceof ObjectType) {
            foreach (['first', 'last'] as $link) {
                $linkType = $paginationLinks->getProperty($link);

                if ($linkType instanceof Type) {
                    $linkType->nullable(false);
                }
            }
        }

        $meta = $schema->type->getProperty('meta');

        if (! $meta instanceof ObjectType) {
            return;
        }

        $meta->addProperty('metric', (new StringType)->enum(PokemonMetric::values()));
        $meta->addProperty('field', (new StringType)->enum(PokemonField::values()));
        $meta->addProperty('order', (new StringType)->enum(RankingOrder::values()));

        $path = $meta->getProperty('path');

        if ($path instanceof Type) {
            $path->nullable(false);
        }

        $perPage = $meta->getProperty('per_page');

        if ($perPage instanceof IntegerType) {
            $perPage->setMin(1);
            $perPage->setMax(100);
        }

        $links = $meta->getProperty('links');

        if ($links instanceof ArrayType && $links->items instanceof ObjectType) {
            $page = new IntegerType;
            $page->setMin(1);
            $page->nullable(true);

            $links->items->addProperty('page', $page);
            $links->items->addRequired(['page']);
        }
    }

    private function findResponse(Operation $operation, int $status): ?Response
    {
        foreach ($operation->responses ?? [] as $response) {
            $resolvedResponse = $response instanceof Reference ? $response->resolve() : $response;

            if ($resolvedResponse instanceof Response && (int) $resolvedResponse->code === $status) {
                return $resolvedResponse;
            }
        }

        return null;
    }

    private function resolveSchema(Schema|Reference|Type|null $schema): ?Schema
    {
        if ($schema instanceof Schema) {
            return $schema;
        }

        if (! $schema instanceof Reference) {
            return null;
        }

        $resolvedSchema = $schema->resolve();

        return $resolvedSchema instanceof Schema ? $resolvedSchema : null;
    }

    private function rankingItemType(): Type
    {
        return new class extends Type
        {
            private const string DESCRIPTION = 'O campo retornado é determinado pelo parâmetro de query `field`.';

            public function __construct()
            {
                parent::__construct('object');

                $this->setDescription(self::DESCRIPTION);
            }

            /** @return array<mixed> */
            public function toArray(): array
            {
                $schema = parent::toArray();

                if (! is_array($schema)) {
                    return [];
                }

                $schema['oneOf'] = array_map(
                    fn (PokemonField $field): array => [
                        'type' => 'object',
                        'properties' => [
                            $field->value => $this->fieldSchema($field),
                        ],
                        'required' => [$field->value],
                        'additionalProperties' => false,
                    ],
                    PokemonField::cases(),
                );

                return $schema;
            }

            /**
             * @return array{type: string|list<string>}
             */
            private function fieldSchema(PokemonField $field): array
            {
                return match ($field) {
                    PokemonField::Name => ['type' => 'string'],
                    PokemonField::BaseExperience => ['type' => ['integer', 'null']],
                    default => ['type' => 'integer'],
                };
            }
        };
    }
}
