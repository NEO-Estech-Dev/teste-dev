# Arquitetura e decisões técnicas — API Pokémon

> **Sobre este arquivo:** referência curta das decisões técnicas e dos limites da solução.

> **Outros documentos:** [README.md](README.md) é o roteiro do avaliador; [teste-pratico.md](teste-pratico.md) resume a entrega.

> Os exemplos de requisição estão também em uma collection para Postman: [pokemon-metrics.postman_collection.json](pokemon-metrics.postman_collection.json).

## Princípio

A arquitetura separa apenas responsabilidades que têm comportamento próprio, fronteira externa ou benefício claro de teste. O projeto não usa repositories genéricos, interfaces com uma única implementação, eventos ou filas sem necessidade.

## Fluxo de ingestão

```text
Command → PokeApiClient → PokemonData → PokemonIngestService → MySQL
```

| Componente | Responsabilidade |
|---|---|
| `IngestPokemonCommand` | opções, progresso e resultado do processo |
| `PokeApiClient` | chamadas HTTP, concorrência, timeout e retry |
| `PokemonData` | normalização do payload externo |
| `PokemonIngestService` | transação, upserts e sincronização de relações |

Essa separação evita acoplar o formato da PokeAPI ao banco e mantém o command legível. A ingestão é idempotente e convergente: atualiza registros existentes e remove stats ou tipos que deixaram de existir na fonte.

## Fluxo da API

```text
Route → FormRequest → PokemonMetricsQuery → JsonResource → JSON
```

- `PokemonMetricsRequest` valida e normaliza parâmetros opcionais.
- `PokemonMetric`, `MetricOrder` e `PokemonField` formam as whitelists.
- `PokemonMetricsQuery` monta o ranking e aplica filtros e paginação.
- `PokemonMetricResource` retorna somente os campos solicitados.

Colunas e direções de ordenação nunca são copiadas diretamente do request, evitando SQL injection em consultas dinâmicas.

## Modelagem

| Tabela | Finalidade |
|---|---|
| `pokemons` | dados principais e métricas diretas |
| `pokemon_stats` | stats como HP, ataque e velocidade |
| `types` | catálogo de tipos |
| `pokemon_type` | relação muitos-para-muitos e ordem dos tipos |

O identificador da PokeAPI é a chave primária de `pokemons`. Restrições únicas sustentam os upserts. `stats_total` é calculado na ingestão para evitar `SUM` e `GROUP BY` em cada ranking.

As estatísticas permanecem em linhas porque esse formato representa diretamente a fonte e permite acrescentar uma stat sem alterar a tabela principal. Tipos são normalizados porque um Pokémon possui mais de um tipo.

## Performance proporcional

- Detalhes da PokeAPI são buscados concorrentemente, pois uma importação completa exige cerca de 1.350 requisições.
- A escrita ocorre em lotes e uma transação por chunk.
- Rankings de stats usam o índice `(stat, base_stat, pokemon_id)`.
- Colunas diretamente ranqueáveis possuem índices próprios.
- O MySQL escolhe o índice; a aplicação não usa `FORCE INDEX`.
- O relacionamento `types` só é carregado quando solicitado.
- Rankings são armazenados no Redis com `Cache::remember()` por 300 segundos.
- A tag `pokemon_metrics` invalida todas as combinações depois da ingestão.

O cache permanece simples: a chave é um hash dos parâmetros validados e o próprio Redis gerencia as tags. Não existe classe de cache, lista manual de chaves ou estado compartilhado dentro dos workers do Octane.

## Infraestrutura

O Docker Compose possui somente:

- `app`: PHP 8.3, Octane e Swoole;
- `mysql`: MySQL 8 com volume persistente e healthcheck.
- `redis`: cache dos rankings com healthcheck.

O entrypoint instala dependências quando necessário, gera `APP_KEY`, aguarda o banco, executa migrations e inicia o servidor. O Makefile é apenas um conjunto opcional de atalhos.

## Bônus

- Sanctum protege a API com tokens pessoais.
- Octane/Swoole executa a aplicação no container.
- Testes rodam em SQLite e MySQL.