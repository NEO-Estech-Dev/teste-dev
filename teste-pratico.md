# Teste Prático — API de Métricas de Pokémons

## Informações técnicas

### Sistema Operacional

Ubuntu 22.04.5 LTS (Jammy)

### Tecnologias utilizadas

* Docker 29.7.2
* PHP 8.4 (CLI)
* Laravel 13.25
* MySQL 8.4

Mais informações sobre o ambiente podem ser encontradas nos arquivos:

* `docker-compose.yml`
* `docker/Dockerfile`

### Debug

O ambiente possui XDebug habilitado.

Mais informações:

* `docker/Dockerfile`
* `docker/php/xdebug.ini`

---

## Requisitos

Antes de iniciar, certifique-se de ter instalado:

* Docker
* Docker Compose

O projeto utiliza Docker para executar a aplicação, banco de dados e demais serviços necessários.

---

## Configuração do ambiente

Clone o repositório e entre no diretório do projeto:

```bash
git clone <repository-url>
cd teste-dev
```

Copie o arquivo de ambiente:

```bash
cp .env.example .env
```

Configure as variáveis de ambiente necessárias no `.env`.

Para o ambiente Docker, as configurações do banco devem utilizar o nome do serviço `mysql` como host:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=pokemon
DB_USERNAME=pokemon
DB_PASSWORD=pokemon
```

> As credenciais acima são destinadas ao ambiente de desenvolvimento/teste. Em um ambiente de produção, utilize um usuário e, principalmente, uma senha mais forte. As credenciais definidas no `.env` devem ser compatíveis com as configurações do serviço `mysql` no `docker-compose.yml`.

---

## Subindo o ambiente

Execute:

```bash
docker compose up -d
```

Verifique se os containers estão em execução:

```bash
docker compose ps
```

Os seguintes serviços devem estar disponíveis:

* `pokemon-api`
* `pokemon-queue`
* `pokemon-mysql`

Os comandos Artisan abaixo podem ser executados diretamente pelo terminal do sistema utilizando `docker compose exec app`.

---

## Instalação das dependências

Instale as dependências do projeto:

```bash
docker compose exec app composer install
```

Gere a chave da aplicação:

```bash
docker compose exec app php artisan key:generate
```

---

## Banco de dados

Execute as migrations:

```bash
docker compose exec app php artisan migrate
```

Para criar os dados necessários para autenticação, execute os seeders:

```bash
docker compose exec app php artisan db:seed
```

O projeto possui um usuário de teste para autenticação da API.

### Usuário de teste

```text
E-mail: test@example.com
Senha: password
```

> Essas credenciais são destinadas exclusivamente à execução e validação do teste.

---

## Ingestão dos Pokémons

Após executar as migrations, a base pode ser populada utilizando o comando de ingestão:

```bash
docker compose exec app php artisan app:ingest-pokemons
```

O processo consulta a PokeAPI e armazena os dados dos Pokémons no banco de dados.

Durante a ingestão, os Jobs responsáveis pelo processamento das informações complementares e das estatísticas são enviados para a fila do Laravel.

O fluxo simplificado é:

```text
app:ingest-pokemons
        ↓
PokeAPI
        ↓
Banco de dados
        ↓
ProcessPokemon
        ↓
Fila do Laravel
        ↓
pokemon-queue
        ↓
Processamento das estatísticas
```

---

## Processamento dos Jobs

O serviço `queue` inicia automaticamente o worker do Laravel ao executar:

```bash
docker compose up -d
```

Portanto, não é necessário executar manualmente `php artisan queue:work`.

O container `pokemon-queue` permanece executando o worker e processa os Jobs enviados para a fila.

Para acompanhar o processamento da fila em tempo real:

```bash
docker compose logs -f queue
```

---

## Autenticação da API

A API utiliza **Laravel Sanctum** para autenticação.

Para obter um token, envie uma requisição `POST` para:

```text
/api/login
```

Exemplo de utilização no Postman ou ferramenta similar:

```text
POST http://localhost:8000/api/login
```

> O endereço deve ser adaptado de acordo com a configuração do ambiente local.

### Requisição

```json
{
    "email": "test@example.com",
    "password": "password"
}
```

### Resposta

```json
{
    "status": "Sucesso",
    "message": "Autenticação realizada com sucesso.",
    "token": "TOKEN",
    "token_type": "Bearer Token"
}
```

Para acessar endpoints protegidos, envie o token utilizando o header:

```text
Authorization: Bearer TOKEN
```

---

## Consulta de métricas

A consulta das métricas dos Pokémons está disponível através de:

```text
GET /api/pokemons/metrics
```

O endpoint requer autenticação via Laravel Sanctum.

### Parâmetros

| Parâmetro | Obrigatório | Valores                                                                 | Padrão |
| --------- | ----------- | ----------------------------------------------------------------------- | ------ |
| `metric`  | Sim         | `hp`, `speed`, `attack`, `special-attack`, `defense`, `special-defense` | `hp`   |
| `sort`    | Não         | `asc`, `desc`                                                           | `desc` |
| `limit`   | Não         | `1` a `100`                                                             | `20`   |

### Exemplos

Consultar os Pokémons com maior HP:

```text
GET /api/pokemons/metrics?metric=hp
```

Consultar os Pokémons com menor Attack:

```text
GET /api/pokemons/metrics?metric=attack&sort=asc
```

Consultar os 10 Pokémons com maior Speed:

```text
GET /api/pokemons/metrics?metric=speed&sort=desc&limit=10
```

### Exemplo de resposta

```json
{
    "status": "Sucesso",
    "message": "API de métricas dos Pokémons",
    "data": [
        {
            "name": "blissey"
        },
        {
            "name": "chansey"
        }
    ]
}
```

A consulta retorna apenas o nome dos Pokémons, conforme especificado no teste.

### Nenhum resultado

Caso nenhum Pokémon seja encontrado para os critérios informados, a API retorna HTTP `200` com:

```json
{
    "status": "Sucesso",
    "message": "Nenhum Pokémon encontrado.",
    "data": []
}
```

Apenas Pokémons que tiveram suas estatísticas processadas são considerados no ranking.

---

## Testes automatizados

Para executar todos os testes:

```bash
docker compose exec app php artisan test
```

Para executar uma classe de teste específica:

```bash
docker compose exec app php artisan test --filter=AuthApiTest
```

Exemplo:

```bash
docker compose exec app php artisan test --filter=PokemonMetricsApiTest
```

Os testes utilizam recursos de mock do Laravel, como `Http::fake()`, para simular as respostas da PokeAPI e evitar dependência de serviços externos durante a execução da suíte.

---

## Observações

* A PokeAPI é utilizada como fonte externa dos dados dos Pokémons.
* A aplicação não depende da PokeAPI durante a execução dos testes automatizados.
* A rota de métricas requer autenticação utilizando Laravel Sanctum.
* Os parâmetros `sort` e `limit` são opcionais e possuem valores padrão.
* O parâmetro `metric` aceita somente as métricas documentadas nesta página.
* Apenas Pokémons que tiveram suas estatísticas processadas são considerados no ranking.
* O processamento das estatísticas é realizado de forma assíncrona através de Jobs.
* O serviço `pokemon-queue` inicia automaticamente o worker responsável pelo processamento da fila.
* O projeto utiliza Docker para padronizar o ambiente de execução.
* O `Dockerfile` está localizado no diretório `docker/`, enquanto o `docker-compose.yml` permanece na raiz do projeto.
* As credenciais de banco de dados presentes no `.env.example` são destinadas exclusivamente ao ambiente de desenvolvimento/teste.