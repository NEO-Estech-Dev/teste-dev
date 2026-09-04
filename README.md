# Pokémon Metrics API

> **Sobre este arquivo:** roteiro rápido para o avaliador executar e validar o projeto.

> **Outros documentos:** [teste-pratico.md](teste-pratico.md) resume a entrega; [ARQUITETURA-E-DECISOES.md](ARQUITETURA-E-DECISOES.md) explica as decisões técnicas sem repetir o passo a passo.

> A collection para Postman [pokemon-metrics.postman_collection.json](pokemon-metrics.postman_collection.json) contém os endpoints e salva automaticamente o token de autenticação na variável `token_pokemon`.

## 1. Subir o ambiente

Requisitos: Docker com Docker Compose. O `make` é opcional.

```sh
cp .env.example .env
docker compose up -d --build
```

O container da aplicação instala as dependências quando necessário, gera a chave, executa as migrations e inicia o Octane/Swoole em `http://localhost:8000`.

Confirme a inicialização:

```sh
docker compose ps
docker compose exec app php artisan migrate:status
```

Devem estar ativos os serviços `app`, `mysql` e `redis`, com todas as migrations executadas.

## 2. Criar o usuário de demonstração

```sh
docker compose exec app php artisan db:seed
```

Credenciais:

```text
E-mail: demo@estech.test
Senha: password
```

## 3. Executar a ingestão

Para uma avaliação rápida:

```sh
docker compose exec app php artisan pokemon:ingest --limit=200
```

Para importar todo o catálogo:

```sh
docker compose exec app php artisan pokemon:ingest
```

O comando usa requisições concorrentes e operações idempotentes. Executá-lo novamente atualiza os dados sem duplicá-los.

Opções úteis:

```text
--limit=200       limita a quantidade
--offset=0        define a posição inicial
--chunk=200       define registros por transação
--concurrency=25  define requisições simultâneas
--fresh           limpa os dados antes da importação
```

## 4. Testar no Postman

1. Importe `pokemon-metrics.postman_collection.json`.
2. Confira `base_url`, `email` e `password` nas variáveis da collection.
3. Execute a pasta **Autenticação**, depois **Métricas**.
4. Execute **Cenários de erro** e finalize com **Encerramento**.

O login preenche `token_pokemon`; as demais requisições usam esse token automaticamente.

## 5. Testar com curl

Obtenha um token:

```sh
curl --request POST http://localhost:8000/api/v1/auth/login \
  --header 'Content-Type: application/json' \
  --data '{"email":"demo@estech.test","password":"password","device_name":"avaliacao"}'
```

Consulte o ranking, substituindo `<TOKEN>`:

```sh
curl 'http://localhost:8000/api/v1/pokemons/metrics?metric=hp&order=desc&fields=id,name,value,types&limit=10' \
  --header 'Authorization: Bearer <TOKEN>'
```

Parâmetros opcionais:

| Parâmetro | Padrão | Valores principais |
|---|---|---|
| `metric` | `hp` | stats da PokeAPI, `height`, `weight`, `base_experience`, `stats_total` |
| `order` | `desc` | `desc`, `best`, `asc`, `worst` |
| `fields` | `name,value` | `id`, `name`, `value`, `height`, `weight`, `base_experience`, `stats_total`, `sprite_url`, `types` |
| `limit` | `10` | 1 a 100 |
| `page` | `1` | página desejada |
| `type` | — | nome do tipo, como `fire` |
| `only_default` | `false` | `true` ou `false` |

Os rankings são armazenados no Redis por 300 segundos. A chave considera os parâmetros validados, e a ingestão invalida todas as respostas pela tag `pokemon_metrics`.

## 6. Executar os testes

SQLite em memória:

```sh
make test
```

MySQL:

```sh
make test-mysql
```

Sem `make`, consulte os comandos equivalentes no próprio [Makefile](Makefile).

Resultado esperado: `25 passed` e `107 assertions`.

Verifique a formatação sem modificar os arquivos:

```sh
docker compose exec app php vendor/bin/pint --test
```

## 7. Encerrar

```sh
docker compose down
```

O banco é preservado. Para removê-lo também, use `docker compose down -v`.
