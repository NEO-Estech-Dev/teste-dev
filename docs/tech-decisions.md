# Decisões Técnicas

Este arquivo resume as escolhas principais da API e o motivo de cada uma.

## Stack

O ambiente roda com Docker via Laravel Sail, para facilitar o Setup do projeto. O container da aplicação também roda com Octane/Swoole.

## Autenticação

As rotas de métricas ficam protegidas com Laravel Sanctum e Bearer Token.

Sanctum foi escolhido por ser leve, nativo do ecossistema Laravel e suficiente para uma API desse tamanho. Usar Passport/OAuth seria excesso para o escopo do teste.

## Endpoint de Métricas

O endpoint principal é:

```http
GET /api/metrics/pokemon
```

Ele aceita `metric`, `field`, `order`, `limit` e `page`.

Os defaults são:

- `metric=hp`
- `field=name`
- `order=desc`
- `limit=10`

Esses valores deixam a rota útil mesmo sem query string: por padrão, ela retorna um ranking dos Pokémon com maior HP, limitado a uma quantidade fácil de ler.

* O parâmetro `field` controla apenas qual campo volta na resposta. 
* A ordenação continua sendo feita pelo valor da métrica escolhida
* Isso evita uma ambiguidade importante: se `metric=attack&field=height`, a API ainda responde o ranking por ataque, apenas mostrando a altura dos Pokémon ranqueados.
* Usei allowlist para métricas e campos permitidos.

## Organização do Endpoint

A controller fica com a responsabilidade de orquestrar somente as chamadas.

A divisão ficou assim:

- `PokemonMetricsRequest`: valida os filtros.
- `PokemonMetricsFilters`: concentra defaults, métricas e campos permitidos.
- `PokemonMetricsQuery`: monta a consulta do ranking.
- `PokemonMetricsResource`: formata o JSON de saída.

## Ingestão de Dados

A ingestão é feita por um Command Artisan:

```bash
php artisan pokeapi:ingest
```

Esse formato foi escolhido porque ingestão é uma tarefa operacional, não uma ação de usuário via HTTP. Também facilita testar, repetir e automatizar depois com scheduler, se necessário.

Por padrão, o comando busca todos os Pokémon da PokeAPI. Para desenvolvimento e validação rápida, ele também aceita:

- `--limit`
- `--offset`
- `--chunk`
- `--fresh`

A ingestão é síncrona porque o volume da PokeAPI é administrável para este teste e porque fica mais simples para o avaliador executar e acompanhar. Em um cenário maior, faria sentido adicionar uma opção assíncrona com filas, workers e retries por job.

Cada Pokémon é salvo dentro de uma transação própria. Se o processo falhar no meio, os registros já processados continuam consistentes. Como a ingestão é idempotente, o comando pode ser executado novamente sem duplicar dados.

## Persistência
Os detalhes da modelagem estão em [`database-design.md`](database-design.md).
