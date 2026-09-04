# 🧪 Teste Técnico – Desenvolvedor PHP (Estech)

### Instruções para execução

1. Configure as variáveis de ambiente:

   ```bash
   cp .env.example .env
   ```

2. Suba os containers Docker:

   ```bash
   docker compose up -d --build
   ```

3. Instale as dependências e configure a aplicação dentro do container `app`:

   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   ```

4. Execute as migrations e faça a ingestão dos dados:

   ```bash
   docker compose exec app php artisan migrate
   docker compose exec app php artisan pokemon:ingest --chunk=200
   ```

   Não há seed separado; os dados iniciais são obtidos pelo comando de ingestão.

5. Para rodar os testes:

   ```bash
   docker compose exec app php artisan test
   ```

6. Para testar o endpoint, utilize os exemplos disponíveis na collection do Postman:

   https://luizflms-team.postman.co/workspace/My-Workspace~1c662700-9c20-4c1a-8fba-5525b035deb8/collection/27404872-d989d723-c2d9-4314-8935-6e22c70f58f9?action=share&creator=27404872

> Observação: caso o banco de dados ainda não esteja pronto quando a migration for executada, aguarde alguns segundos e execute o comando novamente.

### Documentação da API

A documentação da API está disponível em `localhost:8000/docs/api`.
