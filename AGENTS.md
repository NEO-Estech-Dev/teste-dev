# Guia de Agentes do Projeto

Este arquivo orienta agentes e colaboradores que atuarem nesta API de métricas Pokémon.

## Referências do Projeto

- Leia primeiro o plano em [`poke-api-plan.md`](poke-api-plan.md).
- Consulte as decisões técnicas em [`docs/tech-decisions.md`](docs/tech-decisions.md).
- Consulte a modelagem em [`docs/database-design.md`](docs/database-design.md).
- Use o enunciado e guia de execução em [`teste-pratico.md`](teste-pratico.md).
- Mantenha documentos auxiliares dentro de [`docs/`](docs/).

## Idioma e Escrita

- Todos os arquivos `.md` do projeto devem estar em PT-BR com acentuação.
- Nomes de tabelas, colunas, models, rotas e comandos devem permanecer em inglês quando forem artefatos técnicos do código.
- Ao adicionar uma decisão relevante, registre o motivo em [`docs/tech-decisions.md`](docs/tech-decisions.md).
- Ao alterar a modelagem, atualize [`docs/database-design.md`](docs/database-design.md).

## Convenções Técnicas

- Use Laravel, MySQL, Docker/Sail, Octane/Swoole e Sanctum conforme o plano.
- Prefira migrations e constraints para garantir consistência de dados.
- Evite armazenar JSON quando o dado precisar ser filtrado, ordenado, indexado ou relacionado.
- Proteja endpoints de métricas com `auth:sanctum`.
- Mantenha a ingestão idempotente: reexecutar `pokeapi:ingest` não deve duplicar registros.
- Use testes automatizados para mudanças em autenticação, ingestão e métricas.

## Cuidados ao Evoluir

- Faça mudanças pequenas e focadas.
- Não remova documentação do teste sem substituir por instruções equivalentes ou melhores.
- Preserve os porquês das escolhas, porque eles fazem parte da avaliação técnica.
- Antes de finalizar uma mudança relevante, rode `php artisan test`.
