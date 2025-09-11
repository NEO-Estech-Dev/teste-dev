# Pokemon API

Uma API REST desenvolvida com Laravel que utiliza dados da PokéAPI para popular um banco de dados MySQL.

## Tecnologias Utilizadas

- **Laravel 8.x** - Framework PHP
- **Laravel Sanctum** - Autenticação de API
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
│   │   ├── AbilityController.php
│   │   ├── MetricsController.php
│   │   └── AuthController.php
│   ├── Console/Commands/
│   │   └── PopulatePokemonData.php
│   └── Models/
│       ├── Pokemon.php
│       ├── Type.php
│       ├── Ability.php
│       └── User.php
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

## Autenticação

A API utiliza **Laravel Sanctum** para autenticação. Todas as rotas da API (exceto as de autenticação e métricas) requerem um token de acesso.

### Rotas Públicas (sem autenticação)
- `POST /api/auth/register` - Registro de usuário
- `POST /api/auth/login` - Login de usuário
- `GET /api/metrics` - Métricas públicas
- `GET /api/metrics/available` - Métricas disponíveis

### Rotas Protegidas (requerem autenticação)
- `POST /api/auth/logout` - Logout
- `POST /api/auth/logout-all` - Logout de todos os dispositivos
- `GET /api/auth/user` - Dados do usuário autenticado
- Todas as rotas de Pokemon, Types e Abilities

### Como usar a autenticação

#### 1. Registrar um usuário
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Seu Nome",
    "email": "seu@email.com",
    "password": "sua_senha",
    "password_confirmation": "sua_senha"
  }'
```

#### 2. Fazer login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "seu@email.com",
    "password": "sua_senha"
  }'
```

#### 3. Usar o token nas requisições
```bash
curl -X GET http://localhost:8000/api/pokemon \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

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

### Metrics

- `GET /api/metrics` - Análise de métricas dos pokémons
- `GET /api/metrics/available` - Lista métricas e atributos disponíveis

**Parâmetros para `/api/metrics` (todos opcionais):**
- `metric` - Métrica para analisar (padrão: `total_stats`)
- `limit` - Limite de itens (padrão: `10`)
- `order` - Ordenação: `desc` (melhores) ou `asc` (piores) (padrão: `desc`)
- `attribute` - Atributo específico para mostrar (padrão: `name`)

**Métricas disponíveis:**
- `hp` - Hit Points (HP)
- `attack` - Attack Power
- `defense` - Defense Power
- `special_attack` - Special Attack Power
- `special_defense` - Special Defense Power
- `speed` - Speed
- `total_stats` - Total Stats (soma de todos os stats)
- `height` - Height
- `weight` - Weight
- `order` - Pokemon Order (Pokedex number)
- `base_experience` - Base Experience

**Atributos disponíveis:**
- `name` - Pokemon Name
- `pokemon_id` - Pokemon ID from PokeAPI
- `height` - Height
- `weight` - Weight
- `base_experience` - Base Experience
- `hp` - Hit Points
- `attack` - Attack Power
- `defense` - Defense Power
- `special_attack` - Special Attack Power
- `special_defense` - Special Defense Power
- `speed` - Speed
- `total_stats` - Total Stats

## Exemplos de Uso

> **Nota**: Todos os exemplos abaixo assumem que você já possui um token de autenticação. Substitua `SEU_TOKEN_AQUI` pelo token retornado no login.

### Buscar todos os pokémons
```bash
curl -H "Authorization: Bearer SEU_TOKEN_AQUI" http://localhost:8000/api/pokemon
```

### Buscar pokémons por tipo
```bash
curl -H "Authorization: Bearer SEU_TOKEN_AQUI" "http://localhost:8000/api/pokemon?type=fire"
```

### Buscar pokémon específico
```bash
curl -H "Authorization: Bearer SEU_TOKEN_AQUI" http://localhost:8000/api/pokemon/pokemon-id/1
```

### Buscar tipos
```bash
curl -H "Authorization: Bearer SEU_TOKEN_AQUI" http://localhost:8000/api/types
```

### Buscar pokémons de um tipo específico
```bash
curl -H "Authorization: Bearer SEU_TOKEN_AQUI" http://localhost:8000/api/types/1/pokemon
```

### Exemplos de Métricas

#### Top 5 Pokemon com maior total de stats
```bash
curl "http://localhost:8000/api/metrics?metric=total_stats&limit=5&order=desc"
```

#### Top 3 Pokemon com maior ataque
```bash
curl "http://localhost:8000/api/metrics?metric=attack&limit=3&order=desc"
```

#### Top 3 Pokemon com menor velocidade
```bash
curl "http://localhost:8000/api/metrics?metric=speed&limit=3&order=asc"
```

#### Top 5 Pokemon mais pesados, mostrando também a altura
```bash
curl "http://localhost:8000/api/metrics?metric=weight&limit=5&order=desc&attribute=height"
```

#### Top 10 Pokemon com maior HP
```bash
curl "http://localhost:8000/api/metrics?metric=hp&limit=10&order=desc"
```

#### Ver métricas disponíveis
```bash
curl "http://localhost:8000/api/metrics/available"
```

## Banco de Dados

O banco de dados possui as seguintes tabelas:

- **users** - Usuários do sistema (autenticação)
- **personal_access_tokens** - Tokens de acesso do Sanctum
- **pokemon** - Dados principais dos pokémons (inclui stats: hp, attack, defense, special_attack, special_defense, speed, total_stats)
- **types** - Tipos de pokémons
- **abilities** - Habilidades dos pokémons
- **pokemon_types** - Relacionamento muitos-para-muitos entre pokémons e tipos
- **pokemon_abilities** - Relacionamento muitos-para-muitos entre pokémons e habilidades

## Comando para Popular Dados

### Comando Artisan Personalizado

Foi criado um comando Artisan personalizado `pokemon:populate` que consome a PokéAPI e popula o banco de dados de forma eficiente e controlada.

#### Uso Básico
```bash
# Popular 50 pokémons (padrão)
docker-compose exec app php artisan pokemon:populate

# Popular 100 pokémons
docker-compose exec app php artisan pokemon:populate --limit=100

# Popular a partir do pokémon 50
docker-compose exec app php artisan pokemon:populate --offset=50 --limit=50

# Limpar dados existentes antes de popular
docker-compose exec app php artisan pokemon:populate --clear

# Combinar opções
docker-compose exec app php artisan pokemon:populate --limit=150 --offset=0 --clear
```

#### Opções Disponíveis
- `--limit` - Número de pokémons para buscar (padrão: 50)
- `--offset` - Posição inicial para paginação (padrão: 0)
- `--clear` - Limpar dados existentes antes de popular

#### Funcionalidades do Comando
- ✅ Barra de progresso em tempo real
- ✅ Tratamento de erros (continua mesmo se alguns pokémons falharem)
- ✅ Transações de banco (garante consistência dos dados)
- ✅ Estatísticas detalhadas no final
- ✅ Busca stats completos da PokéAPI (HP, Attack, Defense, etc.)

### Seeder Tradicional

O `PokemonSeeder` também está disponível para uso tradicional:

```bash
docker-compose exec app php artisan db:seed --class=PokemonSeeder
```

## Segurança

### Autenticação
- A API utiliza Laravel Sanctum para autenticação baseada em tokens
- Tokens são gerados durante o login e devem ser incluídos no header `Authorization: Bearer {token}`
- Tokens podem ser revogados individualmente ou em massa (logout-all)

### Boas Práticas
- **Nunca** compartilhe tokens de acesso
- Use HTTPS em produção
- Implemente rate limiting para prevenir abuso
- Mantenha tokens seguros no lado do cliente
- Revogue tokens quando não precisar mais deles

### Códigos de Resposta
- `200` - Sucesso
- `201` - Criado com sucesso (registro)
- `401` - Não autenticado (token inválido/ausente)
- `422` - Erro de validação
- `500` - Erro interno do servidor

## Desenvolvimento

Para desenvolvimento local, você pode usar o Laravel Sail (já incluído no projeto):

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```


##Fluxo n8n 

- O Fluxo está disponível para importação no arquivo `chat_pokemon.json`

**Possíveis perguntas:**
- `Quais são os 5 pokemons com maior ataque?` - Lista os 5 pokemons com maior ataque de acordo com o retorno da API.
- `Me traga o pokemon mais leve.` - Traz o pokemon mais leve de acordo com o retorno da API.
- `Liste os top 3 em velocidade, mas só me retorne o nome.` - Lista os 3 top em velocidade, trazendo somente o nome.
- `Quais são as metricas disponíveis?` - Responde com as metricas disponíveis no fluxo.
- `Top 3 pokemons mais altos` - Atributo indisponível no momento, o chat deve retornar quais são os atributos possíveis.


**chave de API do OpenAI**
api_key_openai="[CHAVE_REMOVIDA_POR_SEGURANCA]"