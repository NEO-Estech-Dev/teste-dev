# 🧪 Teste Técnico – Desenvolvedor PHP (Estech)

Neste teste avaliaremos seu **conhecimento técnico**, **organização de código**, **raciocínio lógico** e também sua **velocidade de desenvolvimento**.  
Leia atentamente as instruções antes de iniciar.

---

## 🎯 Objetivo do Desafio

Implementar uma **API REST** utilizando **Laravel (PHP)** e **MySQL**, cujo objetivo é realizar a **ingestão de dados** da API pública de Pokémons e disponibilizar **métricas consultáveis** a partir desses dados.

A API pública a ser utilizada é:  
👉 https://pokeapi.co/

---

## 📋 Requisitos Funcionais

### 1️⃣ Ingestão de Dados

Sua aplicação deve possuir um **Command do Laravel** responsável por:

- Consumir a API pública do PokeAPI;
- Persistir os dados relevantes no banco de dados MySQL;

---

### 2️⃣ Endpoint de Métricas

Criar uma **rota HTTP** que permita consultar métricas dos Pokémons armazenados.

A rota deve permitir, **de forma opcional**, os parâmetros:

- **Métrica a ser analisada**, por exemplo:
  - `hp`

- **Campo específico a ser retornado**
  - Exemplo: retornar apenas o `name` no ranking.

- **Ordenação**
  - Maiores valores (melhores)
  - Menores valores (piores)

📌 **Observação:**  
Todos os parâmetros devem ser **opcionais** e possuir valores padrão coerentes.
Você pode definir outros parâmetros que garantam performance, melhor visibilidade, etc.

---

## 🗄️ Banco de Dados

- O banco de dados deve ser modelado e criado **exclusivamente via Migrations do Laravel**;
- Fique à vontade para definir a melhor modelagem, desde que faça sentido para o domínio do problema.

---

## 🧰 Tecnologias Obrigatórias

O projeto **deve** utilizar:

- PHP
- Framework **Laravel**
- **MySQL**
- **Docker** (para construção do ambiente de desenvolvimento)

---

## 🚀 Entrega do Desafio

Para entregar o teste, siga rigorosamente os passos abaixo:

1. Faça um **fork** deste repositório  
   > ⚠️ Apenas clonar o repositório não permitirá o push.

2. Crie uma **branch com seu nome completo**;

3. Atualize o arquivo `teste-pratico.md`, descrevendo claramente:
   - Como subir o ambiente (Docker);
   - Comandos necessários (migrations, seeds, ingestão de dados, etc);
   - Qualquer observação relevante para execução do projeto.

4. Após finalizar, abra um **Pull Request** para o repositório original.

---

## ⭐ Bônus (Opcional)

- Implementar autenticação de usuários utilizando **Laravel Sanctum**.
- Usar Octane / Swoole
- Criar testes automatizados

---

## 🔍 O que será avaliado?

- Configuração e automação do ambiente com Docker;
- Modelagem e transformação de dados;
- Organização e legibilidade do código;
- Clareza no raciocínio lógico;
- Performance e otimização das consultas;
- Boas práticas com Laravel e PHP.

---

## 🍀 Boa sorte!

Seja claro, simples e consistente. Preferimos soluções bem pensadas a soluções excessivamente complexas.

---

## Como executar o projeto

Arquitetura desacoplada: a aplicação foi desenvolvida como uma **API REST** independente de camada de apresentação. Clientes web ou mobile podem ser desenvolvidos depois sem alterar o domínio.

### Pré-requisitos

- Docker
- Docker Compose

### Subir o ambiente

```bash
cp .env.example .env
docker compose up -d --build
```

No Windows (PowerShell):

```powershell
Copy-Item .env.example .env
docker compose up -d --build
```

Na subida, o container `app` instala dependências se necessário, gera a `APP_KEY` se estiver vazia, roda **migrations** e o **seeder**.

A API fica em `http://localhost:8000`.  
Documentação interativa (OpenAPI): `http://localhost:8000/docs/api`.  
O MySQL do Docker usa a porta **3307** no host (a 3306 costuma estar ocupada por um MySQL local). Dentro da rede Docker a aplicação continua em `mysql:3306`.

Se precisar rerodar o setup manualmente:

```bash
docker compose exec app php artisan app:setup
```

### Ingestão de dados

Importa a 1ª geração (151 Pokémon) por padrão:

```bash
docker compose exec app php artisan pokemons:ingest
```

Opções:

```bash
docker compose exec app php artisan pokemons:ingest --limit=20
docker compose exec app php artisan pokemons:ingest --limit=151 --fresh
docker compose exec app php artisan app:setup --ingest
```

A ingestão é idempotente (upsert). Se um Pokémon falhar, os demais continuam.

### Endpoint de métricas

`GET /api/pokemons/metrics` — rota **pública**.

Parâmetros opcionais:

| Parâmetro | Default | Descrição |
|---|---|---|
| `metric` | `hp` | `hp`, `attack`, `defense`, `special_attack`, `special_defense`, `speed` |
| `order` | `desc` | `desc` (melhores) ou `asc` (piores) |
| `fields` | `name,value` | Campos retornados. Ex.: `name` |
| `per_page` | `20` | Itens por página (máx. 100) |
| `page` | `1` | Página |

Exemplos:

```bash
curl "http://localhost:8000/api/pokemons/metrics"
curl "http://localhost:8000/api/pokemons/metrics?metric=attack&order=desc&per_page=10"
curl "http://localhost:8000/api/pokemons/metrics?metric=hp&fields=name&order=asc"
```

### Autenticação (Sanctum)

Usuário de demonstração criado pelo seeder:

- e-mail: `avaliador@estech.test`
- senha: `password`

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"avaliador@estech.test\",\"password\":\"password\"}"
```

Rotas autenticadas (`Authorization: Bearer {token}`):

- `GET /api/user`
- `POST /api/logout`

Cadastro: `POST /api/register` com `name`, `email`, `password` e `password_confirmation`.

### Testes

```bash
docker compose exec app php artisan test
```

Há também um workflow de CI em `.github/workflows/tests.yml`.

### Observações

- O banco é criado só por **migrations**.
- Stats ficam em colunas indexadas para ranking rápido (`ORDER BY hp DESC`).
- CORS está habilitado para `http://localhost:5173` e `http://localhost:3000` (ajustável em `CORS_ALLOWED_ORIGINS`).
- Respostas seguem `{ data, meta }` / `{ message, errors }`.
- A documentação em `/docs/api` permite testar os endpoints sem front-end.

