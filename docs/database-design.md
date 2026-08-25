# Modelagem do Banco de Dados

Este documento descreve a modelagem inicial do banco de dados da API de métricas Pokémon.

## Visão Geral

A modelagem usa MySQL com tabelas normalizadas para representar os dados principais da PokeAPI:

- Pokémon
- Species
- Stats
- Types
- Abilities

As métricas consultáveis pelo endpoint serão baseadas principalmente em `pokemon_stats.base_stat`.

## Diagrama Lógico

```text
species 1 ─── N pokemons

pokemons N ─── N stats
          via pokemon_stats

pokemons N ─── N types
          via pokemon_type

pokemons N ─── N abilities
          via pokemon_ability
```

## Tabelas

### `species`

Representa dados da espécie do Pokémon vindos do recurso `pokemon-species` da PokeAPI.

| Campo | Tipo | Regras |
| --- | --- | --- |
| `id` | bigint unsigned | primary key |
| `pokeapi_id` | unsigned integer | unique, not null |
| `name` | string | index, not null |
| `base_happiness` | unsigned smallint | nullable |
| `capture_rate` | unsigned smallint | nullable |
| `is_baby` | boolean | default false |
| `is_legendary` | boolean | default false |
| `is_mythical` | boolean | default false |
| `created_at` | timestamp | nullable |
| `updated_at` | timestamp | nullable |

Relacionamentos:

- `species` possui muitos `pokemons`.

### `pokemons`

Representa o Pokémon consultado pelo ranking de métricas.

| Campo | Tipo | Regras |
| --- | --- | --- |
| `id` | bigint unsigned | primary key |
| `pokeapi_id` | unsigned integer | unique, not null |
| `species_id` | bigint unsigned | nullable, foreign key para `species.id` |
| `name` | string | index, not null |
| `height` | unsigned smallint | not null |
| `weight` | unsigned smallint | not null |
| `base_experience` | unsigned integer | nullable |
| `created_at` | timestamp | nullable |
| `updated_at` | timestamp | nullable |

Relacionamentos:

- `pokemon` pertence a uma `species`.
- `pokemon` possui muitas `stats` via `pokemon_stats`.
- `pokemon` possui muitos `types` via `pokemon_type`.
- `pokemon` possui muitas `abilities` via `pokemon_ability`.

### `stats`

Catálogo das métricas numéricas da PokeAPI.

| Campo | Tipo | Regras |
| --- | --- | --- |
| `id` | bigint unsigned | primary key |
| `pokeapi_id` | unsigned integer | unique, nullable |
| `name` | string | unique, not null |
| `created_at` | timestamp | nullable |
| `updated_at` | timestamp | nullable |

Valores esperados:

- `hp`
- `attack`
- `defense`
- `special-attack`
- `special-defense`
- `speed`

Relacionamentos:

- `stat` pertence a muitos `pokemons` via `pokemon_stats`.

### `pokemon_stats`

Tabela pivô entre `pokemons` e `stats`, armazenando o valor da métrica para cada Pokémon.

| Campo | Tipo | Regras |
| --- | --- | --- |
| `id` | bigint unsigned | primary key |
| `pokemon_id` | bigint unsigned | foreign key para `pokemons.id`, not null |
| `stat_id` | bigint unsigned | foreign key para `stats.id`, not null |
| `base_stat` | unsigned integer | not null |
| `effort` | unsigned integer | default 0 |
| `created_at` | timestamp | nullable |
| `updated_at` | timestamp | nullable |

Índices e constraints:

- unique composto: `pokemon_id`, `stat_id`.
- index composto: `stat_id`, `base_stat`.

Uso principal:

- Ordenar Pokémon por uma métrica específica no endpoint `/api/metrics/pokemon`.

### `types`

Catálogo de tipos de Pokémon.

| Campo | Tipo | Regras |
| --- | --- | --- |
| `id` | bigint unsigned | primary key |
| `pokeapi_id` | unsigned integer | unique, nullable |
| `name` | string | unique, not null |
| `created_at` | timestamp | nullable |
| `updated_at` | timestamp | nullable |

Relacionamentos:

- `type` pertence a muitos `pokemons` via `pokemon_type`.

### `pokemon_type`

Tabela pivô entre `pokemons` e `types`.

| Campo | Tipo | Regras |
| --- | --- | --- |
| `id` | bigint unsigned | primary key |
| `pokemon_id` | bigint unsigned | foreign key para `pokemons.id`, not null |
| `type_id` | bigint unsigned | foreign key para `types.id`, not null |
| `slot` | unsigned tinyint | not null |
| `created_at` | timestamp | nullable |
| `updated_at` | timestamp | nullable |

Índices e constraints:

- unique composto: `pokemon_id`, `type_id`.
- unique composto: `pokemon_id`, `slot`.

Comportamento esperado:

- Se um tipo ainda não existir, a ingestão cria o registro em `types`.
- Se dois ou mais Pokémon usarem o mesmo tipo, todos apontam para o mesmo `type_id`.
- A constraint única em `types.name` evita duplicação do catálogo.
- A constraint única em `pokemon_type(pokemon_id, type_id)` evita duplicar o vínculo em reprocessamentos.

### `abilities`

Catálogo de habilidades de Pokémon.

| Campo | Tipo | Regras |
| --- | --- | --- |
| `id` | bigint unsigned | primary key |
| `pokeapi_id` | unsigned integer | unique, nullable |
| `name` | string | unique, not null |
| `created_at` | timestamp | nullable |
| `updated_at` | timestamp | nullable |

Relacionamentos:

- `ability` pertence a muitos `pokemons` via `pokemon_ability`.

### `pokemon_ability`

Tabela pivô entre `pokemons` e `abilities`.

| Campo | Tipo | Regras |
| --- | --- | --- |
| `id` | bigint unsigned | primary key |
| `pokemon_id` | bigint unsigned | foreign key para `pokemons.id`, not null |
| `ability_id` | bigint unsigned | foreign key para `abilities.id`, not null |
| `is_hidden` | boolean | default false |
| `slot` | unsigned tinyint | not null |
| `created_at` | timestamp | nullable |
| `updated_at` | timestamp | nullable |

Índices e constraints:

- unique composto: `pokemon_id`, `ability_id`.
- unique composto: `pokemon_id`, `slot`.

## Índices Principais

| Tabela | Índice | Objetivo |
| --- | --- | --- |
| `pokemons` | unique `pokeapi_id` | idempotência da ingestão |
| `pokemons` | index `name` | busca e ordenação secundária |
| `species` | unique `pokeapi_id` | idempotência da ingestão |
| `stats` | unique `name` | validação de métricas |
| `pokemon_stats` | index `stat_id`, `base_stat` | ranking por métrica |
| `pokemon_stats` | unique `pokemon_id`, `stat_id` | evita duplicação |
| `types` | unique `name` | catálogo normalizado |
| `pokemon_type` | unique `pokemon_id`, `type_id` | evita duplicação |
| `pokemon_type` | unique `pokemon_id`, `slot` | preserva um tipo por posição |
| `abilities` | unique `name` | catálogo normalizado |
| `pokemon_ability` | unique `pokemon_id`, `ability_id` | evita duplicação |
| `pokemon_ability` | unique `pokemon_id`, `slot` | preserva uma habilidade por posição |

## Comportamento Esperado na Ingestão

- Registros vindos da PokeAPI devem ser persistidos de forma idempotente.
- Rodar o command mais de uma vez não deve duplicar Pokémon, stats, types, abilities, species ou vínculos.
- Quando um dado externo mudar, o registro local deve ser atualizado.
- Cada Pokémon é persistido em uma transação própria.
- Se falhar no meio do processo, os Pokémon já concluídos permanecem salvos e consistentes.
- Ao rodar o command novamente, os registros existentes são reutilizados ou atualizados por chaves únicas.

## Consulta de Métricas

O endpoint de métricas deve consultar:

- `stats.name` para identificar a métrica solicitada.
- `pokemon_stats.base_stat` para ordenar os resultados.
- `pokemons` para retornar dados como `name`, `height`, `weight` e `base_experience`.

O campo `field` muda apenas a projeção retornada. A ordenação permanece em `pokemon_stats.base_stat`, exposto na resposta como `metric_value`.

Campos permitidos no parâmetro `field`:

- `id`
- `pokemon_id`
- `name`
- `base_experience`
- `height`
- `weight`
- `metric_value`
