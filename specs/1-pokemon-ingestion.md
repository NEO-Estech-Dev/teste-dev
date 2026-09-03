# Especificação — Fluxo de ingestão de Pokémon

## 1. Objetivo

Definir a ingestão idempotente de Pokémon da PokeAPI para o MySQL. A integração externa deve ficar isolada em `App\Http\Integrations\PokeApi`, seguindo a separação entre Connector, Resource, Data Objects e Data Factories. O fluxo deve buscar a lista paginada, carregar os detalhes na ordem das referências, converter o contrato externo em objetos internos tipados e persistir cada lote com uma única operação de `upsert`.

Esta especificação cobre somente a ingestão. O endpoint HTTP de métricas será tratado separadamente, mas os atributos persistidos aqui devem permitir sua implementação.

## 2. Fluxo principal

```text
php artisan pokemon:ingest
             │
             ▼
IngestPokemonsCommand
             │
             ▼
PokeApiConnector::pokemons()
             │
             ▼
PokemonResource::list()
             │
             ├── PokeApiConnector::send() ──► PokeAPI GET /pokemon
             │
             ▼
PokemonPageFactory::make()
             │
             ▼
PokemonPage + PokemonReference
             │
             ▼
PokemonResource::details()
             │
             ├── PokeApiConnector::pool() ──► requests concorrentes
             │
             ▼
PokemonDetailsFactory::collection()
             │
             ▼
Collection<PokemonDetails>
             │
             ▼
UpsertPokemon::handle()
             │
             ▼
Pokemon::upsert() ──► MySQL
```

O processamento ocorre em lotes. Nenhuma transação de banco deve permanecer aberta durante chamadas HTTP.

## 3. Componentes e responsabilidades

| Componente | Local sugerido | Responsabilidade |
| --- | --- | --- |
| `IngestPokemonsCommand` | `app/Console/Commands` | Validar opções, controlar paginação e lotes, orquestrar dependências e apresentar o resultado |
| `PokeApiConnector` | `app/Http/Integrations/PokeApi` | Configurar a requisição base, registrar-se no container, criar Resources e enviar requisições individuais ou concorrentes |
| `PokemonResource` | `app/Http/Integrations/PokeApi/Resources` | Representar os endpoints de Pokémon e devolver somente Data Objects para a aplicação |
| `PokemonPage`, `PokemonReference` e `PokemonDetails` | `app/Http/Integrations/PokeApi/DataObjects` | Representar contratos internos, imutáveis e tipados para página, referência e detalhes |
| Data Factories correspondentes | `app/Http/Integrations/PokeApi/DataFactories` | Normalizar e converter arrays da PokeAPI em Data Objects |
| `UpsertPokemon` | `app/Actions` | Converter um lote de `PokemonDetails` em linhas e executar uma única persistência em lote |
| `Pokemon` | `app/Models` | Representar a tabela `pokemons` e executar o `upsert` via Eloquent |

O Command não deve conhecer o formato de `results` ou `stats` da PokeAPI nem montar arrays de banco. O Connector não deve conhecer endpoints ou persistir dados. O Resource não deve expor `Response` ou arrays externos. Data Objects e Data Factories não devem realizar I/O.

Estrutura esperada da integração:

```text
app/Http/Integrations/PokeApi/
├── DataFactories/
│   ├── PokemonDetailsFactory.php
│   ├── PokemonPageFactory.php
│   └── PokemonReferenceFactory.php
├── DataObjects/
│   ├── PokemonDetails.php
│   ├── PokemonPage.php
│   └── PokemonReference.php
├── Exceptions/
│   └── InvalidPokeApiPayload.php
├── Resources/
│   └── PokemonResource.php
└── PokeApiConnector.php
```

## 4. Contrato do Command

```php
final class IngestPokemonsCommand extends Command
{
    protected $signature = 'pokemon:ingest
        {--limit= : Maximum number of Pokemons to import}
        {--offset=0 : Starting offset}
        {--chunk=50 : Number of Pokemons processed at once}';

    protected $description = 'Ingest Pokemons from PokeAPI';

    public function handle(
        PokeApiConnector $pokeApi,
        UpsertPokemon $upsertPokemon,
    ): int {
        // Somente orquestração.

        return self::SUCCESS;
    }
}
```

### 4.1 Opções

- `--limit`: quantidade máxima a importar. Quando omitida, processa a partir de `offset` até a PokeAPI não informar uma próxima página.
- `--offset`: posição inicial da listagem. Padrão `0`.
- `--chunk`: quantidade máxima de Pokémon buscada, transformada e persistida em cada lote. Padrão `50`.

Valores aceitos:

- `limit`: inteiro maior que zero quando informado;
- `offset`: inteiro maior ou igual a zero;
- `chunk`: inteiro entre `1` e `200`.

Uma opção inválida deve encerrar antes de qualquer chamada externa e retornar `Command::INVALID`.

Exemplos:

```bash
php artisan pokemon:ingest
php artisan pokemon:ingest --limit=100
php artisan pokemon:ingest --limit=500 --offset=500
php artisan pokemon:ingest --limit=100 --chunk=25
```

### 4.2 Algoritmo de orquestração

1. Ler e validar as opções.
2. Inicializar o cursor com `offset` e a quantidade restante com `limit`, quando fornecido.
3. Calcular o tamanho da página como o menor valor entre `chunk` e a quantidade restante. Sem `limit`, usar `chunk`.
4. Obter `PokemonResource` por `PokeApiConnector::pokemons()` e chamar `list($pageSize, $cursor)`.
5. Receber `PokemonPage` e encerrar com sucesso se `items` estiver vazio.
6. Chamar `PokemonResource::details()` com a coleção de `PokemonReference` retornada.
7. Receber uma coleção de `PokemonDetails`, já convertida e normalizada pelas Data Factories.
8. Entregar a coleção completa a `UpsertPokemon::handle()`.
9. Avançar o cursor pela quantidade de referências recebidas e descontar essa quantidade do limite restante.
10. Repetir enquanto houver uma próxima página e o limite solicitado não tiver sido alcançado.
11. Exibir o total sincronizado e retornar `Command::SUCCESS`.

Um lote só deve ser persistido depois que todos os seus detalhes forem obtidos e transformados com sucesso.

## 5. Integração com a PokeAPI

### 5.1 Connector

`PokeApiConnector` é o ponto de entrada da integração. Seu desenho deve seguir a abordagem simples apresentada no artigo [Working with third party services in Laravel](https://laravel-news.com/working-with-third-party-services-in-laravel): receber um único `PendingRequest`, registrar a própria construção, criar Resources e expor um método genérico para envio. O método `pool()` é a única extensão necessária para este fluxo, pois os detalhes de um lote devem ser buscados concorrentemente.

O Connector não deve validar payloads ou URLs de detalhe, implementar logging, conhecer endpoints ou traduzir exceções. O Connector controla apenas o transporte e o limite de concorrência do pool.

Interface pública esperada:

```php
use Illuminate\Support\Facades\Config;

final readonly class PokeApiConnector
{
    public function __construct(
        private PendingRequest $request,
    ) {
    }

    public function pokemons(): PokemonResource
    {
        return new PokemonResource(connector: $this);
    }

    public function send(string $method, string $uri, array $options = []): Response
    {
        return $this->request->send(
            method: $method,
            url: $uri,
            options: $options,
        )->throw();
    }

    /**
     * @param array<array-key, string> $requests
     * @return array<array-key, Response|Throwable>
     */
    public function pool(array $requests): array
    {
        return Http::pool(
            callback: function (Pool $pool) use ($requests): array {
                $pending = [];

                foreach ($requests as $key => $url) {
                    $pending[] = $pool
                        ->as((string) $key)
                        ->timeout(Config::integer('services.pokeapi.timeout'))
                        ->acceptJson()
                        ->get($url);
                }

                return $pending;
            },
            concurrency: max(0, Config::integer('services.pokeapi.concurrency')),
        );
    }

    public static function register(Application $app): void
    {
        $app->bind(
            abstract: self::class,
            concrete: fn (): self => new self(
                request: Http::baseUrl(
                    url: Config::string('services.pokeapi.base_url'),
                )->timeout(
                    seconds: Config::integer('services.pokeapi.timeout'),
                )->asJson()->acceptJson(),
            ),
        );
    }
}
```

`send()` deve apenas delegar para o `PendingRequest` configurado e chamar `throw()`. Respostas HTTP malsucedidas e falhas de conexão devem continuar usando as exceções do HTTP Client do Laravel.

`pool()` recebe um mapa ordenado de chave para URL e devolve o mapa de respostas ou falhas produzido por `Http::pool()`. Cada requisição deve ser nomeada com `Pool::as()` e receber explicitamente `timeout()` e `acceptJson()`, pois as requisições criadas pelo pool não herdam a configuração do `PendingRequest` injetado. A concorrência deve vir de `Config::integer('services.pokeapi.concurrency')`. O método não deve interpretar status, payloads ou exceções.

Além do construtor e desses quatro métodos públicos — `register()`, `pokemons()`, `send()` e `pool()` — o Connector não deve possuir lógica de retry, logging, validação ou métodos auxiliares.

`AppServiceProvider::register()` deve registrar a integração sem duplicar sua construção:

```php
public function register(): void
{
    PokeApiConnector::register(app: $this->app);
}
```

### 5.2 Resource

`PokemonResource` representa somente os endpoints sob `/pokemon`:

```php
final readonly class PokemonResource
{
    public function __construct(private PokeApiConnector $connector)
    {
    }

    public function list(int $limit, int $offset = 0): PokemonPage
    {
        // GET /pokemon e conversão por PokemonPageFactory.
    }

    /**
     * @param Collection<int, PokemonReference> $pokemons
     * @return Collection<int, PokemonDetails>
     */
    public function details(Collection $pokemons): Collection
    {
        // Pool de detalhes e conversão por PokemonDetailsFactory.
    }
}
```

`list()` chama `GET /pokemon` com `limit` e `offset`. O envelope com `results`, `next` e `count` é entregue a `PokemonPageFactory`, que devolve `PokemonPage`.

`details()` recebe `Collection<int, PokemonReference>` e devolve `Collection<int, PokemonDetails>` na mesma ordem. O Resource trata cada URL recebida da PokeAPI como um valor opaco: não valida nem reescreve esquema, host, porta ou prefixo de caminho. Ele cria diretamente um mapa ordenado, nomeado pelo índice ou identificador de cada referência, e o entrega a `PokeApiConnector::pool()`. Portanto, `PokemonResource` não deve possuir métodos como `ensureCompatibleDetailUrl()` ou `port()`. O Resource percorre as respostas na ordem das referências, trata falhas e status malsucedidos e somente então entrega todos os payloads a `PokemonDetailsFactory::collection()`.

O Resource conhece os endpoints e interpreta as respostas, mas não devolve `Response` ou arrays da PokeAPI para o Command. Um lote só é convertido e persistido depois que todas as requisições concorrentes terminarem com sucesso.

### 5.3 Configuração

Por ser uma integração de terceiro, toda configuração deve ficar na chave `pokeapi` de `config/services.php`. `env()` não deve ser chamado fora de arquivos de configuração.

```php
// config/services.php
'pokeapi' => [
    'base_url' => (string) env('POKEAPI_BASE_URL', 'https://pokeapi.co/api/v2'),
    'timeout' => (int) env('POKEAPI_TIMEOUT', 10),
    'concurrency' => (int) env('POKEAPI_CONCURRENCY', 5),
],
```

As mesmas variáveis devem ser documentadas em `.env.example` e definidas no `.env` de cada ambiente:

```dotenv
POKEAPI_BASE_URL=https://pokeapi.co/api/v2
POKEAPI_TIMEOUT=10
POKEAPI_CONCURRENCY=5
```

O Connector deve ler os valores exclusivamente pelos métodos tipados da facade `Config`: `Config::string()` para `base_url` e `Config::integer()` para `timeout` e `concurrency`. A facade deve ser importada como `Illuminate\Support\Facades\Config`. Como valores vindos do ambiente podem chegar como strings, `config/services.php` deve convertê-los para os tipos esperados ao carregar a configuração. Essa abordagem mantém os tipos conhecidos pelo Larastan sem exigir anotações ou validações auxiliares no Connector.

A concorrência é diferente do tamanho do lote: com `chunk=50` e `concurrency=5`, até cinco das cinquenta requisições de detalhe ficam em voo simultaneamente.

### 5.4 Comportamento HTTP

- Configurar `baseUrl`, `timeout`, `asJson` e `acceptJson` uma única vez no `PendingRequest` criado em `register()`.
- Requisições individuais devem passar por `PokeApiConnector::send()` e lotes concorrentes por `PokeApiConnector::pool()`.
- Aplicar `timeout` e `acceptJson` explicitamente a cada requisição adicionada ao pool.
- Chamar `throw()` imediatamente após o envio e deixar o HTTP Client do Laravel produzir suas exceções nativas.
- Não implementar retries automáticos nesta versão. Uma nova execução do Command é a estratégia de retomada.
- Deixar a conversão e a normalização estrutural dos payloads para as Data Factories.
- Encaminhar as URLs de detalhe fornecidas pela PokeAPI sem validação ou reescrita no `PokemonResource`.
- Não registrar corpos completos das respostas.

## 6. Data Objects e Data Factories

Os Data Objects são valores imutáveis e contextuais. Eles impedem que arrays e nomes do contrato externo se espalhem pelo Command, Action ou Model. A criação a partir de arrays pertence exclusivamente às Data Factories; os Data Objects não devem possuir `fromApi()`.

### 6.1 Data Objects

`PokemonDetails` representa um Pokémon completo e pronto para uso pela aplicação:

```php
final readonly class PokemonDetails
{
    public function __construct(
        public int $pokeapiId,
        public string $name,
        public int $height,
        public int $weight,
        public ?int $baseExperience,
        public int $hp,
        public int $attack,
        public int $defense,
        public int $specialAttack,
        public int $specialDefense,
        public int $speed,
    ) {
    }

    public function toArray(): array
    {
        // Retorna a representação interna sem timestamps.
    }
}
```

Também devem existir:

- `PokemonReference`, com `name` e `url`;
- `PokemonPage`, com `Collection<int, PokemonReference> $items`, `bool $hasNextPage` e `int $total`.

Cada Data Object deve expor `toArray()` para serialização previsível. `PokemonDetails::toArray()` usa nomes internos em camelCase; a tradução para colunas snake_case continua sendo responsabilidade de `UpsertPokemon`, para que o objeto da integração não dependa do esquema MySQL.

Cada Data Object deve possuir teste unitário isolado que cubra suas propriedades readonly e sua representação completa por `toArray()`. Para `PokemonPage`, o teste também deve garantir que as referências aninhadas sejam serializadas como uma lista, independentemente das chaves da `Collection`.

### 6.2 Data Factories

Cada payload cru deve ser convertido por uma classe dedicada:

```php
final readonly class PokemonDetailsFactory
{
    public function make(array $attributes): PokemonDetails
    {
        // Converte um payload de detalhes diretamente para o Data Object.
    }

    /**
     * @return Collection<int, PokemonDetails>
     */
    public function collection(array $items): Collection
    {
        // Retorna Collection<int, PokemonDetails> preservando a ordem.
    }
}
```

`PokemonReferenceFactory::make()` valida `name` e `url`. Seu método `collection()` converte `results` em `Collection<int, PokemonReference>`. `PokemonPageFactory::make()` valida o envelope, usa `PokemonReferenceFactory` para os itens e converte `next` em `hasNextPage`.

`PokemonDetailsFactory::make()` deve assumir o contrato de detalhes fornecido pela PokeAPI e mapear seus valores diretamente para `PokemonDetails`. A factory deve indexar `stats` pelo valor de `stat.name`, obter os seis atributos `hp`, `attack`, `defense`, `special-attack`, `special-defense` e `speed`, aceitar `base_experience` nulo e converter os nomes com hífen para as propriedades camelCase internas.

`PokemonDetailsFactory` não deve criar uma camada própria de validação de tipos ou valores e não deve possuir métodos auxiliares como `positiveInteger()`, `nonNegativeInteger()`, `nullableNonNegativeInteger()`, `nonEmptyString()` ou equivalentes. Os tipos do Data Object e o contrato da PokeAPI definem os valores esperados. Seu método `collection()` deve interromper no primeiro erro de conversão e nunca devolver uma coleção parcialmente convertida.

Exemplo da fronteira:

```text
PokeAPI: stats[*].stat.name = "special-attack"
                         │
                         ▼
PokemonDetails::$specialAttack
                         │
                         ▼
MySQL: special_attack
```

## 7. Persistência em lote

`UpsertPokemon` deve seguir o padrão de Actions da aplicação: classe de propósito único em `app/Actions`, sem sufixo adicional e com um método público `handle()`.

Contrato esperado:

```php
final readonly class UpsertPokemon
{
    /**
     * @param Collection<int, PokemonDetails> $pokemons
     */
    public function handle(Collection $pokemons): void
    {
        // Converte Data Objects em linhas e realiza um único upsert.
    }
}
```

A Action deve gerar um único timestamp para todo o lote e montar as linhas com:

- `pokeapi_id`;
- `name`;
- `height`;
- `weight`;
- `base_experience`;
- `hp`;
- `attack`;
- `defense`;
- `special_attack`;
- `special_defense`;
- `speed`;
- `created_at` e `updated_at`.

Persistência esperada:

```php
Pokemon::upsert(
    $rows,
    ['pokeapi_id'],
    [
        'name',
        'height',
        'weight',
        'base_experience',
        'hp',
        'attack',
        'defense',
        'special_attack',
        'special_defense',
        'speed',
        'updated_at',
    ],
);
```

`created_at` participa somente da inserção; não deve ser sobrescrito em registros já existentes. Uma transação explícita não é necessária enquanto o lote resultar em uma única instrução `upsert`.

## 8. Requisito de banco de dados

A tabela `pokemons` deve ser criada exclusivamente por migration reversível. Estrutura mínima:

| Coluna | Regra |
| --- | --- |
| `id` | chave primária |
| `pokeapi_id` | inteiro sem sinal e `UNIQUE` |
| `name` | string |
| `height` | inteiro sem sinal |
| `weight` | inteiro sem sinal |
| `base_experience` | inteiro sem sinal e anulável |
| seis colunas de stats | inteiros sem sinal |
| `created_at`, `updated_at` | timestamps do Laravel |

O índice único em `pokeapi_id` é obrigatório. No MySQL, ele é o mecanismo que permite ao `upsert` distinguir inserção de atualização e garante idempotência.

Índices adicionais para rankings devem ser definidos junto da especificação do endpoint de métricas, conforme os campos e critérios de ordenação realmente expostos.

### 8.1 Contrato de serialização do Model

`Pokemon::toArray()` deve expor todas as colunas persistidas na ordem do esquema. Esse contrato deve ser coberto por um teste do Model em `tests/Unit/Models/PokemonTest.php`, usando a factory e uma instância recarregada do banco:

```php
it('serializes every persisted column to an array', function (): void {
    $pokemon = Pokemon::factory()->create()->refresh();

    expect(array_keys($pokemon->toArray()))->toEqual([
        'id',
        'pokeapi_id',
        'name',
        'height',
        'weight',
        'base_experience',
        'hp',
        'attack',
        'defense',
        'special_attack',
        'special_defense',
        'speed',
        'created_at',
        'updated_at',
    ]);
});
```

## 9. Falhas, consistência e retomada

- Falha ao listar uma página: não persistir nada dessa página e retornar `Command::FAILURE`.
- Falha em qualquer detalhe do lote: não persistir o lote e retornar `Command::FAILURE`.
- Erro ao converter qualquer item: não persistir o lote e retornar `Command::FAILURE`.
- Falha no banco: retornar `Command::FAILURE` e preservar os lotes já concluídos.
- Em qualquer falha, mostrar o offset do lote e uma mensagem útil, sem expor dados sensíveis.

Lotes anteriores podem permanecer gravados. Uma nova execução é a estratégia de retomada: o índice único e o `upsert` atualizam os registros existentes sem duplicá-los.

## 10. Saída operacional

Durante a execução, o Command deve apresentar progresso por lote. Ao concluir, deve informar pelo menos:

- quantidade solicitada ou total informado pela API;
- quantidade efetivamente sincronizada;
- offset inicial;
- tamanho dos lotes;
- duração total.

O processo deve retornar código diferente de zero se algum lote falhar.

## 11. Critérios de aceite

1. `pokemon:ingest` sem opções percorre todas as páginas disponíveis.
2. `--limit`, `--offset` e `--chunk` alteram o intervalo e o lote conforme seus contratos.
3. Opções inválidas não disparam chamadas HTTP.
4. `PokeApiConnector` é resolvido pelo container após seu `register()` ser chamado em `AppServiceProvider::register()`.
5. O Connector segue o formato mínimo do artigo, estendido somente com `pool()` para concorrência: um `PendingRequest`, `register()`, `pokemons()`, `send()` e `pool()`, sem retries, logging, validação ou métodos auxiliares, lendo configuração pelos métodos tipados `Config::string()` e `Config::integer()`.
6. A listagem usa `GET /pokemon?limit={n}&offset={n}` por meio de `PokemonResource::list()`.
7. A listagem devolve `PokemonPage`; nenhum `Response` ou array externo chega ao Command.
8. Os detalhes são buscados concorrentemente por `PokemonResource::details()`, por meio de `PokeApiConnector::pool()`, respeitando o limite configurado e preservando a ordem das referências, sem validar ou reescrever suas URLs.
9. Cada resposta é convertida diretamente por `PokemonDetailsFactory` em `PokemonDetails` com os seis stats, sem métodos auxiliares de validação de tipos ou valores.
10. Um erro de conversão interrompe o lote e nenhuma coleção parcialmente convertida é persistida.
11. Cada lote gera exatamente um `Pokemon::upsert()`.
12. Duas execuções com os mesmos dados mantêm a mesma quantidade de registros.
13. Uma nova execução atualiza os atributos e `updated_at`, preservando `created_at`.
14. A falha de um detalhe impede a persistência parcial do lote e produz código de saída de falha.
15. Testes automatizados usam `Http::fake()`, bloqueiam chamadas externas não simuladas e cobrem Connector, Resource e Data Factories isoladamente.
16. O teste do Model `Pokemon` garante que `toArray()` exponha todas as colunas persistidas na ordem definida pelo esquema.
17. Testes unitários isolados cobrem a imutabilidade e o contrato de serialização de `PokemonDetails`, `PokemonPage` e `PokemonReference`.

## 12. Fora do escopo desta especificação

- Endpoint e contrato de métricas;
- Autenticação com Sanctum;
- Execução distribuída com filas;
- Retries automáticos;
- Agendamento automático;
- Remoção de Pokémon locais que deixem de aparecer na PokeAPI.
