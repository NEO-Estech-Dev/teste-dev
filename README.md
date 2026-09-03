# Pokémon Metrics API

API REST em Laravel para ingerir dados da [PokeAPI](https://pokeapi.co/) e consultar rankings por atributos de batalha.

## Subir o ambiente

Requisito: Docker com Docker Compose.

```bash
docker compose up -d --build
```

O container aguarda o MySQL ficar saudável, executa as migrations e inicia a API em `http://localhost:8000` com Laravel Octane e Swoole.

Se a porta 8000 já estiver ocupada, defina outra antes de subir (`APP_PORT=8088 docker compose up -d --build`; no PowerShell, `$env:APP_PORT=8088; docker compose up -d --build`).

Para verificar os serviços:

```bash
docker compose ps
curl http://localhost:8000/up
```

## Ingerir os dados

Por padrão, o comando busca todos os Pokémon disponíveis. As requisições de detalhes são concorrentes e os registros são gravados em lotes com `upsert`, portanto o comando pode ser executado novamente sem duplicar dados.

```bash
docker compose exec app php artisan pokemon:ingest
```

Para uma execução curta durante a avaliação:

```bash
docker compose exec app php artisan pokemon:ingest --limit=100
```

Opções disponíveis:

```text
--limit=0         Quantidade máxima; zero busca todos
--batch=25        Registros persistidos por lote (1 a 50)
--concurrency=5   Requisições simultâneas (1 a 10)
--start=0         Offset para retomar manualmente uma execução
```

Não há seed obrigatório: a fonte de dados do domínio é o comando de ingestão.

## Autenticação

O endpoint de métricas usa tokens Bearer do Laravel Sanctum.

Criar usuário:

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"Ash","email":"ash@example.com","password":"pikachu123","password_confirmation":"pikachu123"}'
```

Login:

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"ash@example.com","password":"pikachu123"}'
```

Use o valor de `token` retornado nas consultas:

```bash
curl "http://localhost:8000/api/v1/pokemon/metrics" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN"
```

Revogar o token atual:

```bash
curl -X DELETE http://localhost:8000/api/v1/auth/token \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN"
```

## Métricas

`GET /api/v1/pokemon/metrics`

Todos os parâmetros são opcionais:

| Parâmetro | Padrão | Valores |
| --- | --- | --- |
| `metric` | `hp` | `hp`, `attack`, `defense`, `special_attack`, `special_defense`, `speed` |
| `direction` | `desc` | `desc` (maiores) ou `asc` (menores) |
| `fields` | `name,metric` | Lista entre `pokeapi_id`, `name`, `height`, `weight`, `base_experience`, `types`, `metric` |
| `per_page` | `20` | De 1 a 100 |
| `page` | `1` | Página positiva |

Exemplos:

```bash
# Os 10 Pokémon mais rápidos, retornando nome e valor
curl "http://localhost:8000/api/v1/pokemon/metrics?metric=speed&direction=desc&fields=name,metric&per_page=10" \
  -H "Accept: application/json" -H "Authorization: Bearer SEU_TOKEN"

# Os menores ataques, retornando somente o nome
curl "http://localhost:8000/api/v1/pokemon/metrics?metric=attack&direction=asc&fields=name" \
  -H "Accept: application/json" -H "Authorization: Bearer SEU_TOKEN"
```

## Testes e qualidade

```bash
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint --test
```

Os testes usam SQLite em memória e não alteram o banco MySQL de desenvolvimento. Eles cobrem autenticação, autorização, validação dos filtros, ordenação, seleção de campos, paginação, transformação da PokeAPI e idempotência da ingestão.

## Decisões técnicas

- Uma única tabela `pokemon` mantém a leitura do ranking simples. As seis métricas são colunas numéricas, com índices compostos `(métrica, id)` para ordenação estável e eficiente.
- `types` é JSON porque é um atributo multivalorado que não participa das consultas pedidas. Normalizá-lo adicionaria joins sem benefício neste escopo.
- A ingestão separa acesso externo e transformação em um serviço. Retry, timeout, concorrência limitada e persistência por lote reduzem tempo e impacto de falhas.
- `upsert` por `pokeapi_id` torna a ingestão idempotente e também atualiza dados modificados na origem.
- Métricas e campos são validados contra listas explícitas antes de formar a consulta, evitando entrada arbitrária em nomes de colunas.
- Paginação limita custo e tamanho da resposta. Octane/Swoole mantém a aplicação carregada entre requisições.

## Comandos úteis

```bash
# Logs
docker compose logs -f app

# Executar migrations manualmente (a subida já faz isso)
docker compose exec app php artisan migrate

# Encerrar os containers preservando os dados
docker compose down

# Reiniciar do zero, removendo o volume MySQL
docker compose down -v
```
