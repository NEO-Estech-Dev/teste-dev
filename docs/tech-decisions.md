# Decisões Técnicas da API Pokémon

Este documento registra as principais decisões técnicas do projeto, seus motivos e o impacto esperado na avaliação do teste prático.

## Stack Principal

### Laravel

- Decisão: usar Laravel como framework principal.
- Por que: é uma tecnologia obrigatória do teste e oferece recursos nativos para API REST, comandos Artisan, migrations, validação, HTTP client, testes, Eloquent ORM e autenticação.
- Impacto: a implementação fica alinhada ao que a vaga quer avaliar: domínio de Laravel, organização de código e boas práticas do ecossistema.

### Laravel 13

- Decisão: usar Laravel 13.
- Por que: é a versão definida no planejamento para manter a implementação alinhada à documentação oficial recente.
- Impacto: mostra cuidado com stack atual e facilita consulta à documentação durante a implementação.

### MySQL

- Decisão: usar MySQL como banco relacional.
- Por que: é uma tecnologia obrigatória do teste e combina bem com o problema, que possui entidades relacionáveis: Pokémon, stats, types, abilities e species.
- Impacto: permite demonstrar modelagem relacional, índices, chaves estrangeiras, tabelas pivô e consultas performáticas.
- Alternativa rejeitada: salvar tudo em arquivos ou JSON sem normalização. Isso dificultaria filtros, ordenação, índices e validação relacional.

### Docker

- Decisão: entregar ambiente Docker.
- Por que: é uma exigência do teste e remove dependência do ambiente local do avaliador.
- Impacto: qualquer pessoa consegue subir API e banco com comandos documentados, aumentando a confiabilidade da entrega.

### Laravel Sail + Octane/Swoole

- Decisão: usar Sail como base Docker e ajustar o container para rodar Octane com Swoole.
- Por que: Sail é o caminho oficial do Laravel para desenvolvimento com Docker; Swoole permite cumprir o bônus de Octane sem montar uma imagem customizada complexa.
- Impacto: reduz risco de setup, mantém convenção Laravel e evidencia o bônus opcional de performance.
- Alternativa rejeitada: criar um `docker-compose` totalmente customizado desde o início. Daria mais controle, mas aumentaria o custo de manutenção e o risco de incompatibilidade com Laravel.

## Autenticação e API

### Laravel Sanctum

- Decisão: proteger o endpoint de métricas com `auth:sanctum` e usar Bearer Token.
- Por que: Sanctum é o bônus de autenticação pedido no teste e é adequado para APIs simples baseadas em token.
- Impacto: demonstra autenticação real sem a complexidade de OAuth/Passport.
- Alternativa rejeitada: deixar métricas públicas. Seria mais simples para testar manualmente, mas aproveitaria pior o bônus de autenticação.

### Endpoint Principal de Métricas

- Decisão: expor `GET /api/metrics/pokemon`.
- Por que: o enunciado pede uma rota HTTP para consultar métricas dos Pokémon armazenados.
- Impacto: concentra a regra principal em um endpoint claro e fácil de documentar.

### Ranking Flexível

- Decisão: o endpoint aceita `metric`, `field`, `order`, `limit` e `page`.
- Por que: o enunciado pede métrica analisada, campo específico retornado e ordenação por maiores ou menores valores, todos opcionais. `limit` e `page` são adicionados por performance e usabilidade.
- Impacto: atende diretamente ao requisito e evita respostas grandes demais.

### Ordenação por Métrica

- Decisão: `order` sempre ordena por `metric_value`, enquanto `field` controla apenas o campo retornado.
- Por que: o objetivo da rota é ranking de Pokémon por métrica. Se `field=height` também mudasse a ordenação para altura, a consulta deixaria de responder “maiores ou menores valores de HP/attack/etc.”.
- Impacto: a API mantém semântica clara: `metric` escolhe o ranking, `order` escolhe direção do ranking e `field` escolhe a projeção da resposta.

### Defaults do Endpoint

- Decisão: usar `metric=hp`, `field=name`, `order=desc`, `limit=10` como defaults.
- Por que: todos os parâmetros precisam ser opcionais e ter valores padrão coerentes. HP é uma métrica fácil de entender; ranking descendente mostra os maiores valores primeiro; limitar em 10 melhora leitura e performance.
- Impacto: o endpoint funciona sem query string e ainda retorna um resultado útil.

### Allowlist de Campos e Métricas

- Decisão: validar `metric` e `field` por allowlist.
- Por que: evita expor colunas internas por acidente, reduz risco de SQL injection por campos dinâmicos e mantém a resposta previsível.
- Impacto: filtros dinâmicos ficam seguros e testáveis.
- Campos permitidos em `field`: `id`, `pokemon_id`, `name`, `base_experience`, `height`, `weight`, `metric_value`.

## Ingestão de Dados

### Command Artisan

- Decisão: criar `php artisan pokeapi:ingest`.
- Por que: o enunciado pede explicitamente um Command do Laravel. Ingestão é uma tarefa operacional, não uma requisição HTTP de usuário.
- Impacto: facilita execução manual, testes automatizados e futura automação por scheduler.

### PokeAPI como Fonte Externa

- Decisão: consumir `https://pokeapi.co/api/v2/pokemon` e recursos relacionados.
- Por que: é a API pública obrigatória do teste.
- Impacto: a aplicação transforma dados externos reais em uma base consultável localmente.

### Persistência Local

- Decisão: persistir os dados relevantes no MySQL e consultar métricas sempre no banco local.
- Por que: o teste pede persistir dados relevantes. Consultar localmente é mais rápido, previsível e indexável.
- Impacto: evita que disponibilidade ou lentidão da PokeAPI afetem o endpoint de métricas.
- Alternativa rejeitada: consultar a PokeAPI diretamente no endpoint. Isso simplificaria o banco, mas prejudicaria performance, resiliência e descumpriria o foco de persistência do desafio.

### Todos os Pokémon com Limite Opcional

- Decisão: ingerir todos os Pokémon por padrão e permitir `--limit`, `--offset`, `--chunk` e `--fresh`.
- Por que: todos por padrão demonstra completude; limite opcional acelera desenvolvimento, testes e avaliação rápida.
- Impacto: a solução fica robusta para carga completa e ergonômica para executar em ambiente local.

### Ingestão Síncrona por Padrão

- Decisão: executar a ingestão de forma síncrona no command `pokeapi:ingest`.
- Por que: o teste pede um command executável e auditável; para o volume da PokeAPI, processamento em páginas com transações curtas é suficiente e mais simples de validar.
- Impacto: o avaliador consegue rodar um único comando e acompanhar sucesso ou falha diretamente no terminal.
- Alternativa futura: adicionar uma opção `--queue` para despachar jobs assíncronos. Essa alternativa é útil para volumes maiores, mas aumenta a complexidade operacional porque exige worker, retry de jobs e controle de progresso.

### Falha no Meio da Ingestão

- Decisão: persistir cada Pokémon dentro de uma transação própria e interromper o command quando uma chamada ou persistência falhar.
- Por que: transações curtas evitam perder tudo quando há falha no item atual, e interromper cedo deixa o erro explícito para correção.
- Impacto: registros já processados permanecem válidos; ao rodar o command novamente, a idempotência por chaves únicas atualiza ou reutiliza os dados existentes sem duplicar.

### Upsert e Idempotência

- Decisão: usar `upsert`/atualização por chaves únicas na ingestão.
- Por que: permite reexecutar a ingestão sem duplicar registros e simplifica atualização quando a PokeAPI muda algum dado.
- Impacto: prova idempotência, que é uma boa prática em pipelines de ingestão.

### Retry, Timeout e Logs

- Decisão: usar HTTP client do Laravel com retry, timeout e logs.
- Por que: chamadas externas podem falhar, demorar ou retornar erro temporário.
- Impacto: melhora robustez da ingestão e facilita diagnóstico quando algo falhar.

## Modelagem de Dados

### Domínio Amplo Moderado

- Decisão: persistir Pokémon, species, stats, types e abilities, sem moves/evoluções na primeira entrega.
- Por que: esses dados demonstram modelagem relacional suficiente para o teste; moves/evoluções aumentariam muito volume e complexidade sem serem necessários para as métricas pedidas.
- Impacto: equilíbrio entre boa modelagem e simplicidade, evitando uma solução excessivamente complexa.

### `pokemons`

- Decisão: criar tabela central com `pokeapi_id`, `name`, `height`, `weight`, `base_experience` e `species_id`.
- Por que: centraliza a entidade consultada pelo ranking e evita depender da API externa nas consultas.
- Impacto: torna a consulta de métricas simples, relacional e indexável.

### `species`

- Decisão: separar dados de espécie em tabela própria.
- Por que: na PokeAPI, `pokemon` e `pokemon-species` são recursos diferentes; manter essa separação respeita o domínio da API de origem.
- Impacto: melhora a expressividade da modelagem sem sobrecarregar a tabela `pokemons`.

### `stats`

- Decisão: criar catálogo de stats como `hp`, `attack`, `defense`, `special-attack`, `special-defense` e `speed`.
- Por que: evita colunas fixas para cada stat e permite validar `metric` por dados persistidos.
- Impacto: facilita extensão e deixa a métrica dinâmica sem mudar schema.
- Alternativa rejeitada: colunas `hp`, `attack`, `speed` direto em `pokemons`. Funcionaria para o mínimo, mas acoplaria o schema a um conjunto fixo de stats e demonstraria menos modelagem.

### `pokemon_stats`

- Decisão: criar tabela pivô com `pokemon_id`, `stat_id`, `base_stat` e `effort`.
- Por que: `base_stat` pertence ao vínculo Pokémon + stat, não ao catálogo de stats.
- Impacto: ranking por métrica vira uma consulta indexável por `stat_id` e `base_stat`.

### `types` e `pokemon_type`

- Decisão: modelar tipos como N:N com `slot` no relacionamento.
- Por que: Pokémon pode ter um ou dois tipos; `slot` preserva a ordem/origem da PokeAPI.
- Impacto: representa corretamente o domínio e evita perder informação do relacionamento.

### Tipos Compartilhados

- Decisão: manter `types.name` e `types.pokeapi_id` como únicos e criar vínculos em `pokemon_type`.
- Por que: o mesmo tipo, como `grass`, pode ser usado por muitos Pokémon. O tipo deve existir uma única vez no catálogo e ser compartilhado por vários registros.
- Impacto: se dois ou mais Pokémon usarem o mesmo tipo, a ingestão reutiliza o registro existente e cria apenas novos vínculos na pivô.
- Proteção adicional: a unicidade composta em `pokemon_type(pokemon_id, type_id)` impede duplicar o mesmo tipo para o mesmo Pokémon em reprocessamentos.

### Concorrência em Catálogos

- Decisão: na versão síncrona, a ingestão evita concorrência de escrita no mesmo catálogo. Em uma versão assíncrona, as mesmas constraints únicas continuariam obrigatórias.
- Por que: jobs paralelos poderiam tentar criar o mesmo tipo ao mesmo tempo.
- Impacto: o banco continua sendo a última barreira de consistência; uma evolução assíncrona deve tratar colisões de unicidade com `upsert` ou nova leitura após erro de duplicidade.

### `abilities` e `pokemon_ability`

- Decisão: modelar abilities como N:N com `is_hidden` e `slot` no relacionamento.
- Por que: abilities pertencem a vários Pokémon e têm metadados próprios no vínculo.
- Impacto: evita duplicação e permite consultas futuras por abilities normais ou ocultas.
- Alternativa rejeitada: salvar abilities como texto no Pokémon. A mesma ability pode aparecer em vários Pokémon, então normalizar evita duplicação e melhora consultas futuras.

### Índices e Unicidade

- Decisão: criar `pokemons.pokeapi_id` único, `pokemons.name` indexado, `stats.name` único, `pokemon_stats(stat_id, base_stat)` e unicidade composta nas tabelas pivô.
- Por que: `pokeapi_id` garante idempotência; `stats.name` localiza métricas com segurança; `pokemon_stats(stat_id, base_stat)` otimiza a consulta central; unicidade nas pivôs evita duplicação em reingestões.
- Impacto: melhora consistência dos dados e performance do ranking.

## Testes e Validação

### Testes Automatizados

- Decisão: criar testes para autenticação, validação dos filtros, ranking, retorno por campo, command de ingestão com HTTP fake e idempotência.
- Por que: testes automatizados são bônus opcional e cobrem as partes mais importantes do desafio.
- Impacto: aumenta confiança da entrega e reduz risco de regressão.

### HTTP Fake na Ingestão

- Decisão: testar command de ingestão com respostas falsas da PokeAPI.
- Por que: teste automatizado não deve depender da disponibilidade da API externa.
- Impacto: suite mais rápida, determinística e confiável.

### Subagente de Validação

- Decisão: depois da implementação principal, criar um subagente revisor independente para executar o fluxo completo.
- Por que: simula uma revisão independente, reduz viés de quem implementou e aumenta confiança antes da entrega.
- Impacto: valida a experiência real do avaliador: migrations, ingestão, registro, login, métricas, filtros, logout e testes.

## Documentação

### Atualização do `teste-pratico.md`

- Decisão: documentar setup, migrations, ingestão, autenticação, consultas e testes no próprio `teste-pratico.md`.
- Por que: a entrega exige atualizar esse arquivo e o avaliador provavelmente vai procurar as instruções nele.
- Impacto: facilita revisão, execução e entendimento das decisões tomadas.

### Documento de Decisões Técnicas

- Decisão: manter este `tech-decisions.md` separado do plano operacional.
- Por que: o plano orienta a implementação; este documento explica a defesa técnica das escolhas.
- Impacto: melhora clareza para revisão e para aprendizado durante o processo.

## Referências

- [PokeAPI docs](https://pokeapi.co/docs/v2)
- [Laravel 13](https://laravel.com/framework/docs/13.x)
- [Laravel Sanctum](https://laravel.com/framework/docs/13.x/sanctum)
- [Laravel Sail](https://laravel.com/framework/docs/13.x/sail)
- [Laravel Octane](https://laravel.com/framework/docs/13.x/octane)
