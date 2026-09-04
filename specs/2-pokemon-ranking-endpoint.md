# Especificação — Endpoint de ranking de Pokémon

## 1. Objetivo

Disponibilizar um endpoint HTTP somente de leitura para consultar rankings paginados dos Pokémon já persistidos. O cliente escolhe, opcionalmente, a métrica usada na ordenação, o campo do Pokémon projetado na resposta, a direção do ranking, a página e a quantidade de resultados por página.

Esta especificação cobre somente a consulta dos dados locais. A ingestão da PokeAPI continua definida em `specs/1-pokemon-ingestion.md` e não deve ser disparada pelo endpoint.

## 2. Contrato HTTP

```http
GET /api/v1/pokemons/ranking
Accept: application/json
```

Nome da rota:

```text
api.v1.pokemons.ranking
```

A rota é pública nesta versão e deve usar o middleware padrão do grupo `api`. `routes/api.php` deve ser registrado pela chave `api` de `withRouting()` em `bootstrap/app.php`.

### 2.1 Parâmetros de query

Todos os parâmetros são opcionais:

| Parâmetro | Valores aceitos | Padrão | Finalidade |
| --- | --- | --- | --- |
| `metric` | `hp`, `attack`, `defense`, `special_attack`, `special_defense`, `speed`, `height`, `weight`, `base_experience` | `hp` | Coluna numérica usada para formar o ranking |
| `field` | `pokeapi_id`, `name`, `height`, `weight`, `base_experience`, `hp`, `attack`, `defense`, `special_attack`, `special_defense`, `speed` | `name` | Único atributo do Pokémon projetado em cada posição |
| `order` | `desc`, `asc` | `desc` | `desc` retorna os maiores valores; `asc`, os menores |
| `page` | inteiro maior ou igual a `1` | `1` | Página solicitada |
| `per_page` | inteiro entre `1` e `100` | `10` | Quantidade máxima de posições por página |

`id`, timestamps e nomes arbitrários de coluna não fazem parte do contrato público.

Os padrões fazem a chamada sem parâmetros representar a primeira página dos Pokémon com maior `hp`, identificados por `name`, com dez posições por página.

### 2.2 Exemplos

```http
GET /api/v1/pokemons/ranking
GET /api/v1/pokemons/ranking?metric=attack
GET /api/v1/pokemons/ranking?metric=speed&field=pokeapi_id&order=asc&page=2&per_page=5
GET /api/v1/pokemons/ranking?metric=weight&field=name&order=desc&per_page=20
```

## 3. Resposta de sucesso

Status: `200 OK`.

```json
{
    "data": [
        {
            "name": "blissey"
        },
        {
            "name": "chansey"
        }
    ],
    "links": {
        "first": "http://localhost/api/v1/pokemons/ranking?page=1",
        "last": "http://localhost/api/v1/pokemons/ranking?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "links": [
            {
                "url": null,
                "label": "Previous",
                "page": null,
                "active": false
            },
            {
                "url": "http://localhost/api/v1/pokemons/ranking?page=1",
                "label": "1",
                "page": 1,
                "active": true
            },
            {
                "url": null,
                "label": "Next",
                "page": null,
                "active": false
            }
        ],
        "path": "http://localhost/api/v1/pokemons/ranking",
        "per_page": 10,
        "to": 2,
        "total": 2,
        "metric": "hp",
        "field": "name",
        "order": "desc"
    }
}
```

Cada item de `data` contém exclusivamente o atributo solicitado em `field`. Em páginas não vazias, a posição global no ranking é `meta.from` somada ao índice do item na página, considerando o primeiro índice como `0`. A métrica usada não é incluída implicitamente; para recebê-la em `data`, o cliente deve escolhê-la também como `field`.

O envelope deve seguir a paginação de API Resources do Laravel. `links` permite navegar entre páginas e `meta` informa a página atual, a última página, os links numerados, o intervalo retornado, a quantidade por página e o total de registros elegíveis. Os parâmetros `metric`, `field`, `order` e `per_page`, quando fornecidos pelo cliente, devem ser preservados nos links gerados. URLs e rótulos do exemplo são ilustrativos e devem usar a URL e a tradução reais da aplicação.

Se não houver Pokémon armazenado, a resposta continua sendo `200 OK`:

```json
{
    "data": [],
    "links": {
        "first": "http://localhost/api/v1/pokemons/ranking?page=1",
        "last": "http://localhost/api/v1/pokemons/ranking?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "links": [
            {
                "url": null,
                "label": "Previous",
                "page": null,
                "active": false
            },
            {
                "url": "http://localhost/api/v1/pokemons/ranking?page=1",
                "label": "1",
                "page": 1,
                "active": true
            },
            {
                "url": null,
                "label": "Next",
                "page": null,
                "active": false
            }
        ],
        "path": "http://localhost/api/v1/pokemons/ranking",
        "per_page": 10,
        "to": null,
        "total": 0,
        "metric": "hp",
        "field": "name",
        "order": "desc"
    }
}
```

Campos numéricos devem ser serializados como números. `base_experience`, quando escolhido como `field`, pode ser `null`.

## 4. Regras do ranking

1. Ordenar pela coluna indicada por `metric`, usando a direção indicada por `order`.
2. Desempatar por `pokeapi_id` na mesma direção do ranking. Assim, a ordenação é determinística e pode percorrer o mesmo índice tanto de forma direta quanto reversa.
3. Empates no valor da métrica continuam ocupando posições distintas no array, definidas pelo desempate.
4. Aplicar `per_page` e o deslocamento correspondente a `page` no banco de dados por meio do paginator do Laravel, antes de materializar a coleção.
5. Selecionar somente o campo solicitado. A métrica e `pokeapi_id` podem participar de `ORDER BY` sem integrar a projeção da resposta.
6. Quando `metric=base_experience`, excluir registros cujo valor seja `null`; ausência de experiência base não deve ser interpretada como melhor nem pior resultado.
7. Calcular `total` sobre os mesmos registros elegíveis ao ranking, incluindo a exclusão de `base_experience=null` quando aplicável.
8. Manter a ordenação estável entre páginas enquanto a base não sofrer escritas. Não é necessário garantir um snapshot entre requisições durante uma ingestão concorrente.

A paginação deve usar `LengthAwarePaginator`, por meio de `paginate()`, para expor páginas numeradas, total de registros e última página. Uma página válida acima de `last_page` retorna `200 OK`, `data=[]` e os metadados coerentes com a página solicitada. O teto de `100` em `per_page` evita consultas e respostas sem limite.

## 5. Validação e segurança

Criar um Form Request dedicado em `app/Http/Requests/Api/V1/PokemonRankingRequest.php`.

Regras esperadas:

```php
[
    'metric' => ['sometimes', 'string', Rule::in(/* métricas permitidas */)],
    'field' => ['sometimes', 'string', Rule::in(/* campos permitidos */)],
    'order' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
    'page' => ['sometimes', 'integer', 'min:1'],
    'per_page' => ['sometimes', 'integer', 'between:1,100'],
]
```

O Request deve fornecer os valores padrão quando os parâmetros forem omitidos, preferencialmente mesclando-os em `prepareForValidation()`, e seu método `authorize()` deve retornar `true`. Como a regra `integer` valida, mas não converte o valor da query automaticamente, getters tipados do Request devem entregar `page` e `per_page` como `int`. O Controller e a Query devem receber valores já normalizados, sem ler diretamente parâmetros não validados.

Os identificadores de coluna usados em `select` e `orderBy` nunca podem vir livremente da requisição. Mesmo após a validação HTTP, `RankPokemonsByMetricQuery` deve traduzir `metric`, `field` e `order` por allowlists fechadas antes de montar a consulta. Essa defesa mantém a Query segura quando chamada futuramente por Commands, Jobs ou outros transportes.

Parâmetros inválidos devem produzir a resposta JSON padrão de validação do Laravel com status `422 Unprocessable Content` e uma entrada em `errors` para cada parâmetro inválido. Nenhuma consulta de ranking deve ser executada quando a validação falhar.

Exemplos inválidos:

```http
GET /api/v1/pokemons/ranking?metric=unknown
GET /api/v1/pokemons/ranking?field=password
GET /api/v1/pokemons/ranking?order=random
GET /api/v1/pokemons/ranking?page=0
GET /api/v1/pokemons/ranking?page=invalid
GET /api/v1/pokemons/ranking?per_page=0
GET /api/v1/pokemons/ranking?per_page=101
```

## 6. Componentes e responsabilidades

| Componente | Local esperado | Responsabilidade |
| --- | --- | --- |
| Rota | `routes/api.php` | Expor `GET /v1/pokemons/ranking` com nome estável |
| `PokemonRankingRequest` | `app/Http/Requests/Api/V1` | Validar e normalizar todos os parâmetros opcionais |
| `PokemonRankingController` | `app/Http/Controllers/Api/V1` | Entregar os valores validados à Query e devolver o Resource |
| `RankPokemonsByMetricQuery` | `app/Queries` | Montar e executar a consulta segura, ordenar, paginar e projetar o resultado |
| `RankedPokemonResource` | `app/Http/Resources/Api/V1` | Serializar cada posição no contrato público |
| Migration de índices | `database/migrations` | Criar índices compatíveis com as métricas expostas |

Fluxo:

```text
GET /api/v1/pokemons/ranking
             │
             ▼
PokemonRankingRequest
  valida + aplica padrões
             │
             ▼
PokemonRankingController
             │
             ▼
RankPokemonsByMetricQuery::handle()
  select + whereNotNull + orderBy + paginate
             │
             ▼
RankedPokemonResource
             │
             ▼
200 JSON com data + links + meta
```

### 6.1 Controller

O Controller deve ser invocável e permanecer responsável somente pela camada HTTP:

```php
final class PokemonRankingController extends Controller
{
    public function __invoke(
        PokemonRankingRequest $request,
        RankPokemonsByMetricQuery $rankPokemonsByMetricQuery,
    ): AnonymousResourceCollection {
        // Executa a Query e adiciona os metadados ao Resource.
    }
}
```

Não deve haver query Eloquent, allowlists ou transformação manual de itens no Controller.

### 6.2 Query

`RankPokemonsByMetricQuery` deve ser uma classe de leitura de propósito único, com um único método público `handle()`. Ela deve encapsular a construção e a execução da consulta, retornando o paginator pronto ao Controller. A Query não deve conhecer o Request HTTP nem o Resource.

Contrato lógico:

```php
final readonly class RankPokemonsByMetricQuery
{
    /** @return LengthAwarePaginator<int, Pokemon> */
    public function handle(
        string $metric,
        string $field,
        string $order,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        // Consulta e composição do ranking.
    }
}
```

A consulta deve ser executada por Eloquent a partir de `Pokemon::query()`. Não usar `DB::raw()` nem interpolar valores recebidos na SQL. A Query não precisa de transação, pois realiza apenas leitura.

Depois dos filtros e da ordenação, a Query deve paginar explicitamente com os argumentos equivalentes a `paginate(perPage: $perPage, columns: [$field], pageName: 'page', page: $page)`. Ela deve retornar o `LengthAwarePaginator`, e não um `Builder`, para que os detalhes de persistência e paginação não sejam transferidos ao Controller.

### 6.3 Resource

`RankedPokemonResource` deve preservar exclusivamente esta estrutura por item:

```json
{
    "name": "blissey"
}
```

O envelope `data`, os links e os metadados de paginação devem ser produzidos a partir do `LengthAwarePaginator` pelo mecanismo de Resource Collection do Laravel. O paginator contém instâncias de `Pokemon` parcialmente selecionadas, e o Resource deve serializar somente o atributo presente.

Os valores normalizados de `metric`, `field` e `order` devem ser adicionados a `meta`. Os links de navegação devem receber, por `withQuery()` ou mecanismo equivalente, somente `metric`, `field`, `order` e `per_page` já validados. Não usar `preserveQuery()`, pois parâmetros desconhecidos não devem ser propagados para os links.

## 7. Consulta e índices

A paginação produz duas consultas conceituais. A primeira calcula o total de registros elegíveis:

```sql
SELECT COUNT(*)
FROM pokemons
WHERE {metric} IS NOT NULL;
```

A segunda carrega somente a página solicitada:

```sql
SELECT {field}
FROM pokemons
WHERE {metric} IS NOT NULL
ORDER BY {metric} {order}, pokeapi_id {order}
LIMIT {per_page}
OFFSET ({page} - 1) * {per_page};
```

As chaves entre chaves representam identificadores obtidos somente das allowlists internas; não representam interpolação de entrada arbitrária.

`paginate()` deve executar ambas para produzir `total` e `last_page`. A consulta de dados deve continuar selecionando somente o campo solicitado; ordenação e contagem permanecem no banco de dados. Páginas muito profundas exigem percorrer as entradas anteriores por causa do `OFFSET`, custo aceitável para o volume deste domínio. Paginação por cursor poderá ser considerada se o conjunto crescer significativamente.

Criar uma nova migration reversível, sem alterar a migration original, com índices compostos para cada métrica pública e seu desempate por `pokeapi_id`:

```text
(hp, pokeapi_id)
(attack, pokeapi_id)
(defense, pokeapi_id)
(special_attack, pokeapi_id)
(special_defense, pokeapi_id)
(speed, pokeapi_id)
(height, pokeapi_id)
(weight, pokeapi_id)
(base_experience, pokeapi_id)
```

O mesmo índice B-tree atende leituras ascendentes e descendentes porque as duas colunas usam a mesma direção em cada consulta. O custo adicional de escrita é aceitável neste domínio: a ingestão acontece em lotes, enquanto os rankings podem ser consultados repetidamente.

Não adicionar cache nesta versão. Sem uma estratégia de invalidação integrada ao `UpsertPokemon`, o cache poderia servir rankings obsoletos após uma ingestão.

## 8. Testes automatizados

Os testes devem usar Pest e factories. Como a funcionalidade atravessa HTTP e banco, os cenários principais são Feature tests.

Arquivos esperados:

```text
tests/Feature/Http/Controllers/Api/V1/PokemonRankingControllerTest.php
tests/Feature/Http/Resources/Api/V1/RankedPokemonResourceTest.php
tests/Feature/Queries/RankPokemonsByMetricQueryTest.php
```

Cobertura mínima:

1. A chamada sem parâmetros usa `hp`, `name`, `desc`, `page=1` e `per_page=10`.
2. `metric` altera a coluna usada no ranking.
3. `field` faz cada item de `data` conter somente a chave solicitada.
4. `order=desc` retorna maiores valores e `order=asc` retorna menores valores.
5. `page` navega entre páginas sem repetir ou omitir registros de uma base estável.
6. `per_page` restringe a quantidade por página e aceita os extremos `1` e `100`.
7. `links` preserva `metric`, `field`, `order` e `per_page`.
8. Cada métrica e campo documentado é aceito.
9. Métrica, campo, ordem, página e quantidade por página inválidos retornam `422`.
10. Valores empatados são ordenados deterministicamente por `pokeapi_id`, inclusive entre páginas.
11. `base_experience=null` é ignorado quando essa é a métrica e não integra `total`.
12. `base_experience=null` é preservado quando esse é apenas o campo projetado.
13. Uma última página parcial apresenta `from`, `to`, `total` e `last_page` coerentes.
14. Banco vazio retorna `200`, `data=[]`, `total=0` e links sem próxima ou página anterior.
15. Página acima da última retorna `200` com `data=[]`, `from=null`, `to=null` e os demais metadados coerentes.
16. A quantidade em `data` nunca excede `per_page`, e `per_page` nunca excede `100`.
17. A rota responde apenas a `GET` e possui o nome `api.v1.pokemons.ranking`.

Os testes de ordenação devem criar poucos registros com valores explícitos e distintos; não devem depender de valores aleatórios da factory para estabelecer a posição esperada.

## 9. Critérios de aceite

1. `GET /api/v1/pokemons/ranking` funciona sem parâmetros e devolve o ranking padrão.
2. Todos os parâmetros são opcionais, validados e possuem os padrões documentados.
3. Maiores e menores valores podem ser consultados com `desc` e `asc`.
4. Cada item expõe somente o campo de Pokémon solicitado.
5. A ordenação é determinística para valores empatados.
6. Registros sem `base_experience` não participam de um ranking dessa métrica.
7. O ranking usa paginação numerada com `page`, `per_page`, `links`, `total` e `last_page`.
8. `per_page` é aplicado na consulta e nunca ultrapassa `100`.
9. Os links de navegação preservam os parâmetros que definem o ranking.
10. A consulta seleciona somente as colunas necessárias e usa allowlists para identificadores e direção.
11. O Controller permanece fino, e a lógica reutilizável de leitura fica em `RankPokemonsByMetricQuery`.
12. A resposta é serializada por API Resource e contém `data`, `links` e `meta`.
13. Uma migration reversível cria todos os índices compostos definidos nesta especificação.
14. Os testes automatizados cobrem padrões, projeção, ordenação, paginação, empates, nulos, validação e banco vazio.

## 10. Fora do escopo

- Agregações como média, mediana, soma ou desvio padrão;
- Combinação de várias métricas em um único ranking;
- Filtros por tipo, habilidade ou geração, pois esses dados não são persistidos atualmente;
- Paginação por cursor;
- Snapshot consistente entre requisições durante uma ingestão concorrente;
- Cache e invalidação de rankings;
- Autenticação com Sanctum;
- Consulta direta à PokeAPI durante a requisição;
- Alterações no fluxo de ingestão.
