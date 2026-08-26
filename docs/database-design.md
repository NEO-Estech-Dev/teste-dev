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

| Campo | Tipo | Regras | Significado |
| --- | --- | --- | --- |
| `id` | bigint unsigned | primary key | Identificador interno da espécie no banco. |
| `pokeapi_id` | unsigned integer | unique, not null | Identificador da espécie na PokeAPI. |
| `name` | string | index, not null | Nome da espécie. |
| `base_happiness` | unsigned smallint | nullable | Felicidade base informada pela PokeAPI. |
| `capture_rate` | unsigned smallint | nullable | Taxa base de captura da espécie. |
| `is_baby` | boolean | default false | Indica se a espécie é classificada como bebê. |
| `is_legendary` | boolean | default false | Indica se a espécie é lendária. |
| `is_mythical` | boolean | default false | Indica se a espécie é mítica. |
| `created_at` | timestamp | nullable | Data de criação local do registro. |
| `updated_at` | timestamp | nullable | Data da última atualização local do registro. |

Relacionamentos:

- `species` possui muitos `pokemons`.

### `pokemons`

Representa o Pokémon consultado pelo ranking de métricas.

| Campo | Tipo | Regras | Significado |
| --- | --- | --- | --- |
| `id` | bigint unsigned | primary key | Identificador interno do Pokémon no banco. |
| `pokeapi_id` | unsigned integer | unique, not null | Identificador do Pokémon na PokeAPI. |
| `species_id` | bigint unsigned | nullable, foreign key para `species.id` | Espécie relacionada a este Pokémon. |
| `name` | string | index, not null | Nome da forma específica do Pokémon. |
| `height` | unsigned smallint | not null | Altura informada pela PokeAPI. |
| `weight` | unsigned smallint | not null | Peso informado pela PokeAPI. |
| `base_experience` | unsigned integer | nullable | Experiência base concedida pelo Pokémon. |
| `created_at` | timestamp | nullable | Data de criação local do registro. |
| `updated_at` | timestamp | nullable | Data da última atualização local do registro. |

Relacionamentos:

- `pokemon` pertence a uma `species`.
- `pokemon` possui muitas `stats` via `pokemon_stats`.
- `pokemon` possui muitos `types` via `pokemon_type`.
- `pokemon` possui muitas `abilities` via `pokemon_ability`.

Observação: `pokemons.name` e `species.name` podem ter o mesmo valor, mas representam conceitos diferentes na PokeAPI. `pokemons.name` identifica a forma específica usada nas métricas, enquanto `species.name` identifica a espécie, que concentra dados como `capture_rate`, `is_legendary` e `is_mythical`.

Exemplos onde isso importa:
- `pikachu` pode ter variações/costumes/formas diferentes, mas pertence à espécie `pikachu`.
- `deoxys-normal`, `deoxys-attack`, `deoxys-defense` são Pokémon/formas diferentes, mas ligados à espécie `deoxys`.

### `stats`

Catálogo das métricas numéricas da PokeAPI.

| Campo | Tipo | Regras | Significado |
| --- | --- | --- | --- |
| `id` | bigint unsigned | primary key | Identificador interno da métrica no banco. |
| `pokeapi_id` | unsigned integer | unique, nullable | Identificador da métrica na PokeAPI. |
| `name` | string | unique, not null | Nome da métrica, como `hp` ou `attack`. |
| `created_at` | timestamp | nullable | Data de criação local do registro. |
| `updated_at` | timestamp | nullable | Data da última atualização local do registro. |

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

| Campo | Tipo | Regras | Significado |
| --- | --- | --- | --- |
| `id` | bigint unsigned | primary key | Identificador interno do vínculo no banco. |
| `pokemon_id` | bigint unsigned | foreign key para `pokemons.id`, not null | Pokémon que recebeu a métrica. |
| `stat_id` | bigint unsigned | foreign key para `stats.id`, not null | Métrica associada ao Pokémon. |
| `base_stat` | unsigned integer | not null | Valor base da métrica para o Pokémon. |
| `effort` | unsigned integer | default 0 | Valor de esforço associado à métrica. |
| `created_at` | timestamp | nullable | Data de criação local do vínculo. |
| `updated_at` | timestamp | nullable | Data da última atualização local do vínculo. |

Índices e constraints:

- unique composto: `pokemon_id`, `stat_id`.
- index composto: `stat_id`, `base_stat`, `pokemon_id`.

Uso principal:

- Ordenar Pokémon por uma métrica específica no endpoint `/api/metrics/pokemon`.
- O índice inclui `pokemon_id` para ajudar o MySQL a filtrar pela métrica, ordenar pelo valor e resolver o join com `pokemons` com menos leituras extras.
- Quando há empate no valor da métrica, `pokemon_id` é usado como desempate para manter a resposta estável e aproveitar o mesmo índice.

### `types`

Catálogo de tipos de Pokémon.

| Campo | Tipo | Regras | Significado |
| --- | --- | --- | --- |
| `id` | bigint unsigned | primary key | Identificador interno do tipo no banco. |
| `pokeapi_id` | unsigned integer | unique, nullable | Identificador do tipo na PokeAPI. |
| `name` | string | unique, not null | Nome do tipo, como `fire` ou `water`. |
| `created_at` | timestamp | nullable | Data de criação local do registro. |
| `updated_at` | timestamp | nullable | Data da última atualização local do registro. |

Relacionamentos:

- `type` pertence a muitos `pokemons` via `pokemon_type`.

### `pokemon_type`

Tabela pivô entre `pokemons` e `types`.

| Campo | Tipo | Regras | Significado |
| --- | --- | --- | --- |
| `id` | bigint unsigned | primary key | Identificador interno do vínculo no banco. |
| `pokemon_id` | bigint unsigned | foreign key para `pokemons.id`, not null | Pokémon que possui o tipo. |
| `type_id` | bigint unsigned | foreign key para `types.id`, not null | Tipo associado ao Pokémon. |
| `slot` | unsigned tinyint | not null | Posição do tipo na lista retornada pela PokeAPI. |
| `created_at` | timestamp | nullable | Data de criação local do vínculo. |
| `updated_at` | timestamp | nullable | Data da última atualização local do vínculo. |

Índices e constraints:

- unique composto: `pokemon_id`, `type_id`.
- unique composto: `pokemon_id`, `slot`.
- index composto: `type_id`, `pokemon_id`.

Comportamento esperado:

- Se um tipo ainda não existir, a ingestão cria o registro em `types`.
- Se dois ou mais Pokémon usarem o mesmo tipo, todos apontam para o mesmo `type_id`.
- A constraint única em `types.name` evita duplicação do catálogo.
- A constraint única em `pokemon_type(pokemon_id, type_id)` evita duplicar o vínculo em reprocessamentos.
- O índice `type_id`, `pokemon_id` prepara a tabela para consultas reversas, como listar Pokémon por tipo, sem depender de varredura da pivô.

### `abilities`

Catálogo de habilidades de Pokémon.

| Campo | Tipo | Regras | Significado |
| --- | --- | --- | --- |
| `id` | bigint unsigned | primary key | Identificador interno da habilidade no banco. |
| `pokeapi_id` | unsigned integer | unique, nullable | Identificador da habilidade na PokeAPI. |
| `name` | string | unique, not null | Nome da habilidade. |
| `created_at` | timestamp | nullable | Data de criação local do registro. |
| `updated_at` | timestamp | nullable | Data da última atualização local do registro. |

Relacionamentos:

- `ability` pertence a muitos `pokemons` via `pokemon_ability`.

### `pokemon_ability`

Tabela pivô entre `pokemons` e `abilities`.

| Campo | Tipo | Regras | Significado |
| --- | --- | --- | --- |
| `id` | bigint unsigned | primary key | Identificador interno do vínculo no banco. |
| `pokemon_id` | bigint unsigned | foreign key para `pokemons.id`, not null | Pokémon que possui a habilidade. |
| `ability_id` | bigint unsigned | foreign key para `abilities.id`, not null | Habilidade associada ao Pokémon. |
| `is_hidden` | boolean | default false | Indica se a habilidade é oculta. |
| `slot` | unsigned tinyint | not null | Posição da habilidade na lista retornada pela PokeAPI. |
| `created_at` | timestamp | nullable | Data de criação local do vínculo. |
| `updated_at` | timestamp | nullable | Data da última atualização local do vínculo. |

Índices e constraints:

- unique composto: `pokemon_id`, `ability_id`.
- unique composto: `pokemon_id`, `slot`.
- index composto: `ability_id`, `pokemon_id`.

## Índices Principais

| Tabela | Índice | Objetivo |
| --- | --- | --- |
| `pokemons` | unique `pokeapi_id` | idempotência da ingestão |
| `pokemons` | index `name` | busca e ordenação secundária |
| `species` | unique `pokeapi_id` | idempotência da ingestão |
| `stats` | unique `name` | validação de métricas |
| `pokemon_stats` | index `stat_id`, `base_stat`, `pokemon_id` | ranking por métrica e join com `pokemons` |
| `pokemon_stats` | unique `pokemon_id`, `stat_id` | evita duplicação |
| `types` | unique `name` | catálogo normalizado |
| `pokemon_type` | unique `pokemon_id`, `type_id` | evita duplicação |
| `pokemon_type` | unique `pokemon_id`, `slot` | preserva um tipo por posição |
| `pokemon_type` | index `type_id`, `pokemon_id` | consulta reversa por tipo |
| `abilities` | unique `name` | catálogo normalizado |
| `pokemon_ability` | unique `pokemon_id`, `ability_id` | evita duplicação |
| `pokemon_ability` | unique `pokemon_id`, `slot` | preserva uma habilidade por posição |
| `pokemon_ability` | index `ability_id`, `pokemon_id` | consulta reversa por habilidade |
