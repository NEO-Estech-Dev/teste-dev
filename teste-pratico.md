# Teste Técnico: API de Métricas Pokémon

API REST em Laravel para ingerir dados da PokeAPI, salvar em MySQL e consultar rankings de Pokémon por métricas.

## Collection Postman (validação da API)

Tem um collection pronta para importar para o postman em [collection](./docs/poke-api.postman_collection.json)

## Ambiente

Requisitos:

- Docker e Docker Compose.
- PHP e Composer apenas para instalar as dependências iniciais do Laravel Sail.

Instale as dependências:

```bash
composer install
```

Crie o `.env` e gere a chave da aplicação:

```bash
cp .env.example .env
php artisan key:generate
```

Suba o ambiente Docker:

```bash
./vendor/bin/sail up -d
```

A API ficará disponível em:

```text
http://localhost:8000
```

## Banco de Dados

(Aguarde até o container MySQL ficar disponível)
Execute as migrations:

```bash
./vendor/bin/sail artisan migrate
```
## Ingestão de Dados

Ingerir todos os Pokémon disponíveis na PokeAPI:

```bash
./vendor/bin/sail artisan pokeapi:ingest
```

Ingestão rápida para validação:

```bash
./vendor/bin/sail artisan pokeapi:ingest --fresh --limit=20
```

Para usar a ingestão assíncrona, deixe o Horizon rodando em outro terminal:

```bash
./vendor/bin/sail artisan horizon
```

Depois despache a ingestão:

```bash
./vendor/bin/sail artisan pokeapi:ingest --async --fresh --limit=100 --chunk=50
```

Painel do Horizon:

```text
http://localhost:8000/horizon
```

Se os jobs aparecerem em `Pending Jobs` e não saírem dali, confirme se o worker está ativo:

```bash
./vendor/bin/sail artisan horizon:status
```

Opções úteis:

```bash
./vendor/bin/sail artisan pokeapi:ingest --limit=100 --offset=0 --chunk=50
```

A ingestão padrão é síncrona, paginada e idempotente. Com `--async`, o comando cria jobs na fila Redis `pokeapi-ingestion` e retorna um `batch_id`. Se falhar no meio, os Pokémon já processados permanecem consistentes e o comando pode ser executado novamente sem duplicar registros.

## Autenticação

Registrar usuário:

```bash
curl -sS -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Ash","email":"ash@example.com","password":"password123"}'
```

Login:

```bash
curl -sS -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"ash@example.com","password":"password123"}'
```

Use o `token` retornado como Bearer Token.

Logout:

```bash
curl -sS -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer SEU_TOKEN"
```

## Métricas

Endpoint protegido:

```http
GET /api/metrics/pokemon
```

Exemplo:

```bash
curl -sS "http://localhost:8000/api/metrics/pokemon?metric=hp&field=name&order=desc&limit=10" \
  -H "Authorization: Bearer SEU_TOKEN"
```

Parâmetros:

| Parâmetro | Padrão | Valores |
| --- | --- | --- |
| `metric` | `hp` | `hp`, `attack`, `defense`, `special-attack`, `special-defense`, `speed` |
| `field` | `name` | `id`, `pokemon_id`, `name`, `base_experience`, `height`, `weight`, `metric_value` |
| `order` | `desc` | `asc`, `desc` |
| `limit` | `10` | inteiro entre `1` e `100` |
| `page` | `1` | paginação padrão |

## Testes

```bash
./vendor/bin/sail artisan test
```

Também é possível rodar localmente:

```bash
php artisan test
```

## Observações

- O container da aplicação executa Laravel Octane com Swoole.
- A ingestão assíncrona usa Redis e pode ser acompanhada pelo Horizon em `/horizon` no ambiente local.
- As rotas de métricas exigem autenticação via Laravel Sanctum.
- Os comandos de banco devem ser executados via Sail, pois o `.env` usa `DB_HOST=mysql`.
- Decisões técnicas: [`docs/tech-decisions.md`](docs/tech-decisions.md).
- Modelagem do banco: [`docs/database-design.md`](docs/database-design.md).
