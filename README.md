# Pokemon API

Uma API REST desenvolvida com Laravel que utiliza dados da PokéAPI para popular um banco de dados MySQL.

## Tecnologias Utilizadas

- **Laravel 8.x** - Framework PHP
- **MySQL 8.0** - Banco de dados
- **Docker** - Containerização
- **Nginx** - Servidor web
- **PHP 8.2** - Linguagem de programação

## Estrutura do Projeto

```
pokemon-api/
├── app/
│   ├── Http/Controllers/Api/
│   │   ├── PokemonController.php
│   │   ├── TypeController.php
│   │   └── AbilityController.php
│   └── Models/
│       ├── Pokemon.php
│       ├── Type.php
│       └── Ability.php
├── database/
│   ├── migrations/
│   └── seeders/
│       └── PokemonSeeder.php
├── docker/
│   ├── nginx/
│   └── php/
├── docker-compose.yml
├── Dockerfile
└── routes/api.php
```

## Como Executar

### 1. Clone o repositório
```bash
git clone <url-do-repositorio>
cd pokemon-api
```

### 2. Execute com Docker
```bash
docker-compose up -d
```

### 3. Execute as migrations e seeders
```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

### 4. Acesse a API
A API estará disponível em: `http://localhost:8000`

## Endpoints da API

### Pokemon

- `GET /api/pokemon` - Lista todos os pokémons (com paginação e filtros)
- `GET /api/pokemon/{id}` - Busca pokémon por ID interno
- `GET /api/pokemon/pokemon-id/{pokemonId}` - Busca pokémon por ID da PokéAPI

**Parâmetros de filtro para `/api/pokemon`:**
- `name` - Filtrar por nome
- `type` - Filtrar por tipo
- `ability` - Filtrar por habilidade
- `per_page` - Itens por página (padrão: 15)

### Types

- `GET /api/types` - Lista todos os tipos
- `GET /api/types/{id}` - Busca tipo por ID
- `GET /api/types/{id}/pokemon` - Lista pokémons de um tipo específico

**Parâmetros de filtro para `/api/types`:**
- `name` - Filtrar por nome

### Abilities

- `GET /api/abilities` - Lista todas as habilidades
- `GET /api/abilities/{id}` - Busca habilidade por ID
- `GET /api/abilities/{id}/pokemon` - Lista pokémons com uma habilidade específica

**Parâmetros de filtro para `/api/abilities`:**
- `name` - Filtrar por nome
- `is_hidden` - Filtrar por tipo de habilidade (true/false)

## Exemplos de Uso

### Buscar todos os pokémons
```bash
curl http://localhost:8000/api/pokemon
```

### Buscar pokémons por tipo
```bash
curl "http://localhost:8000/api/pokemon?type=fire"
```

### Buscar pokémon específico
```bash
curl http://localhost:8000/api/pokemon/pokemon-id/1
```

### Buscar tipos
```bash
curl http://localhost:8000/api/types
```

### Buscar pokémons de um tipo específico
```bash
curl http://localhost:8000/api/types/1/pokemon
```

## Banco de Dados

O banco de dados possui as seguintes tabelas:

- **pokemon** - Dados principais dos pokémons
- **types** - Tipos de pokémons
- **abilities** - Habilidades dos pokémons
- **pokemon_types** - Relacionamento muitos-para-muitos entre pokémons e tipos
- **pokemon_abilities** - Relacionamento muitos-para-muitos entre pokémons e habilidades

## Seeder

O `PokemonSeeder` busca automaticamente os primeiros 50 pokémons da PokéAPI e popula o banco de dados com suas informações, incluindo tipos e habilidades.

Para executar apenas o seeder:
```bash
docker-compose exec app php artisan db:seed --class=PokemonSeeder
```

## Desenvolvimento

Para desenvolvimento local, você pode usar o Laravel Sail (já incluído no projeto):

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

## Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request