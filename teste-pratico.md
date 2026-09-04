## Considerações
- Se estiver sem tempo, recomendo seguir pelo [README.md](README.md) e utilizar a collection do Postman para testar, mas também tem comandos cURL no arquivo.

- Entendo que este projeto não precise de muitas camadas ou uma arquitetura muito elaborada, mas para demonstração de conhecimento, achei melhor implementar uma arquitetura "intermediária".

- Como não estava definido no projeto e não utilizamos uma plataforma monitorada, fiz uso de IAs para acelerar o desenvolvimento, pois sabemos que é o dia a dia das empresas hoje em dia. Além disso, acredito que o foco do teste é verificar se o candidato é capaz de resolver o problema utilizando as ferramentas, conhecimento e criatividade.

- Se necessário, fico à disposição para uma conversa para confirmarmos a experiência com o ecossistema PHP/Laravel e bancos relacionais 🙏

## Solução entregue

> **Sobre este arquivo:** arquivo geral sobre a solução implementada.

> **Outros documentos:** [README.md](README.md) é o roteiro prático para testes; [ARQUITETURA-E-DECISOES.md](ARQUITETURA-E-DECISOES.md) detalha a arquitetura e decisões técnicas.

> Os exemplos de requisição estão também em uma **collection para Postman**: [pokemon-metrics.postman_collection.json](pokemon-metrics.postman_collection.json).


A solução usa Laravel 13, PHP 8.3+, MySQL 8 e Docker. Os três bônus foram implementados: autenticação com Sanctum, Octane/Swoole e testes automatizados.

### Execução

```sh
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan db:seed
docker compose exec app php artisan pokemon:ingest
```

As migrations são executadas automaticamente na inicialização. Para uma ingestão rápida, use `--limit=200`. O command aceita ainda `--offset`, `--chunk`, `--concurrency` e `--fresh`.

O usuário de demonstração é `demo@estech.test`, com senha `password`. O passo a passo de autenticação, métricas e Postman está no [README.md](README.md).

### Endpoint

```http
GET /api/v1/pokemons/metrics
```

Todos os parâmetros são opcionais. Os principais são `metric`, `fields` e `order`; também estão disponíveis `limit`, `page`, `type` e `only_default`.

A rota exige token Sanctum por padrão. Para uma inspeção local sem autenticação, defina `METRICS_REQUIRE_AUTH=false` e reinicie a aplicação.

### Testes e qualidade

```sh
make test
make test-mysql
docker compose exec app php vendor/bin/pint --test
```

A suíte contém 25 testes relevantes, com 107 asserções, cobrindo autenticação, validação, rankings, cache, filtros, paginação, seleção de campos, ingestão, atualização, idempotência e falhas parciais da PokeAPI.

### Observações

- A ingestão usa `Http::pool()`, escrita em lotes e transações.
- `upsert` e chaves naturais permitem reexecutar o command sem duplicação.
- Rankings usam whitelists e índices; nenhum nome de coluna vem diretamente do request.
- Rankings são armazenados no Redis por 300 segundos com `Cache::remember()` e tags nativas.
- A ingestão invalida a tag `pokemon_metrics`, disponibilizando os dados atualizados imediatamente.
- Toda a modelagem do domínio é criada exclusivamente por migrations.
