# Instruções para Executar sem Docker

Como o Docker não está disponível no momento, você pode executar o projeto usando o ambiente local do Laravel:

## Pré-requisitos

- PHP 8.0 ou superior
- Composer
- MySQL 8.0
- Extensões PHP: pdo_mysql, mbstring, exif, pcntl, bcmath, gd, zip

## Passos para Executar

### 1. Instalar dependências
```bash
composer install
```

### 2. Configurar banco de dados
Edite o arquivo `.env` com suas credenciais do MySQL:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pokemon_db
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### 3. Criar banco de dados
```bash
mysql -u seu_usuario -p -e "CREATE DATABASE pokemon_db;"
```

### 4. Executar migrations
```bash
php artisan migrate
```

### 5. Executar seeder
```bash
php artisan db:seed
```

### 6. Iniciar servidor
```bash
php artisan serve
```

A API estará disponível em: `http://localhost:8000`

## Testando a API

### Exemplos de requisições:

```bash
# Listar pokémons
curl http://localhost:8000/api/pokemon

# Buscar pokémons por tipo
curl "http://localhost:8000/api/pokemon?type=fire"

# Buscar pokémon específico por ID da PokéAPI
curl http://localhost:8000/api/pokemon/pokemon-id/1

# Listar tipos
curl http://localhost:8000/api/types

# Listar habilidades
curl http://localhost:8000/api/abilities
```

## Estrutura da API

A API possui os seguintes endpoints:

### Pokemon
- `GET /api/pokemon` - Lista todos os pokémons (com paginação e filtros)
- `GET /api/pokemon/{id}` - Busca pokémon por ID interno
- `GET /api/pokemon/pokemon-id/{pokemonId}` - Busca pokémon por ID da PokéAPI

### Types
- `GET /api/types` - Lista todos os tipos
- `GET /api/types/{id}` - Busca tipo por ID
- `GET /api/types/{id}/pokemon` - Lista pokémons de um tipo específico

### Abilities
- `GET /api/abilities` - Lista todas as habilidades
- `GET /api/abilities/{id}` - Busca habilidade por ID
- `GET /api/abilities/{id}/pokemon` - Lista pokémons com uma habilidade específica

## Filtros Disponíveis

### Para `/api/pokemon`:
- `name` - Filtrar por nome
- `type` - Filtrar por tipo
- `ability` - Filtrar por habilidade
- `per_page` - Itens por página (padrão: 15)

### Para `/api/types`:
- `name` - Filtrar por nome

### Para `/api/abilities`:
- `name` - Filtrar por nome
- `is_hidden` - Filtrar por tipo de habilidade (true/false)
