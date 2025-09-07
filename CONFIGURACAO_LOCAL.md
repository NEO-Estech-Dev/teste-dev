# Configuração para Desenvolvimento Local

Para executar o projeto sem Docker, você precisa ajustar as configurações do banco de dados.

## Passo 1: Configurar o arquivo .env

Edite o arquivo `.env` e altere as seguintes linhas:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pokemon_db
DB_USERNAME=root
DB_PASSWORD=sua_senha_aqui
```

## Passo 2: Criar o banco de dados

Execute no MySQL:
```sql
CREATE DATABASE pokemon_db;
```

## Passo 3: Executar migrations

```bash
php artisan migrate
```

## Passo 4: Executar seeder

```bash
php artisan db:seed
```

## Passo 5: Iniciar servidor

```bash
php artisan serve
```

## Testando a API

Com o servidor rodando em `http://localhost:8000`, você pode testar os endpoints:

```bash
# Listar pokémons
curl http://localhost:8000/api/pokemon

# Buscar pokémons por tipo
curl "http://localhost:8000/api/pokemon?type=fire"

# Buscar pokémon específico
curl http://localhost:8000/api/pokemon/pokemon-id/1

# Listar tipos
curl http://localhost:8000/api/types

# Listar habilidades
curl http://localhost:8000/api/abilities
```

## Estrutura do Projeto Criada

✅ **Ambiente Docker** - Configurado com Laravel, MySQL e Nginx
✅ **Projeto Laravel** - Estrutura básica criada
✅ **Banco de Dados** - Migrations para pokemon, types, abilities e relacionamentos
✅ **Models** - Pokemon, Type, Ability com relacionamentos
✅ **Seeders** - PokemonSeeder que busca dados da PokéAPI
✅ **Controllers** - API REST com filtros e paginação
✅ **Rotas** - Endpoints configurados
✅ **Documentação** - README e instruções de uso

## Funcionalidades Implementadas

- **API REST completa** para Pokemon, Types e Abilities
- **Filtros avançados** por nome, tipo, habilidade
- **Paginação** automática
- **Relacionamentos** muitos-para-muitos
- **Integração com PokéAPI** para popular dados
- **Ambiente Docker** pronto para produção
- **Documentação completa** com exemplos de uso
