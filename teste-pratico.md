# 🧪 Teste Técnico – Desenvolvedor PHP (Estech)

Neste teste avaliaremos seu **conhecimento técnico**, **organização de código**, **raciocínio lógico**, **perfomance**, **escalabilidade** e também sua **velocidade de desenvolvimento**.  
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

- Caso sinta necessidade de outras tecnologias complementares, fique a vontade para utilizar.

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

Respostas do teste-pratico:

# Teste Prático – API de Métricas de Pokémons

## Como subir o ambiente

1. Clone o repositório e entre na pasta do projeto Laravel:
  cd pokemon-api
2. Copie o arquivo de ambiente:
  cp .env.example .env
3. Instale as dependências:
  composer install
4. Suba os containers com Sail:
  ./vendor/bin/sail up -d
5. Gere a chave da aplicação (se necessário):
  ./vendor/bin/sail artisan key:generate

## Banco de dados

Rodar as migrations:
./vendor/bin/sail artisan migrate

## Ingestão de dados da PokeAPI
./vendor/bin/sail artisan pokemon:ingest --limit=151

O parâmetro `--limit` é opcional (padrão: 151, cobrindo a primeira geração de pokémons). A ingestão utiliza `updateOrCreate`, podendo ser executada múltiplas vezes sem duplicar dados.

## Endpoint de métricas
GET /api/metrics

### Parâmetros (todos opcionais)

| Parâmetro | Padrão | Descrição |
|---|---|---|
| `metric` | `hp` | Stat a analisar (hp, attack, defense, special-attack, special-defense, speed) |
| `field` | `name` | Campo do pokémon a retornar (name, pokeapi_id, height, weight, base_experience, sprite_url) |
| `order` | `desc` | `desc` (maiores/melhores) ou `asc` (menores/piores) |
| `limit` | `10` | Quantidade de resultados (máximo 100) |

Parâmetros inválidos retornam erro HTTP 422 com detalhes da validação.

### Exemplo de requisição
GET /api/metrics?metric=attack&order=asc&limit=5

### Exemplo de resposta

```json
{
  "metric": "attack",
  "field": "name",
  "order": "asc",
  "limit": 5,
  "results": [
    { "name": "caterpie", "base_value": 30 },
    { "name": "metapod", "base_value": 20 }
  ]
}
```

## Modelagem do banco de dados

- **pokemons**: dados gerais de cada pokémon (pokeapi_id, name, height, weight, base_experience, sprite_url)
- **pokemon_stats**: estatísticas (hp, attack, defense, etc.) separadas em tabela própria, permitindo consultar qualquer métrica sem alterar o schema. Possui índice composto (`stat_name`, `base_value`) para otimizar as consultas de ranking, e índice único (`pokemon_id`, `stat_name`) para evitar duplicidade.

## Observações

- Autenticação via Laravel Sanctum foi instalada na estrutura do projeto, disponível para uso caso necessário.
- A ingestão é idempotente: pode ser executada repetidamente sem gerar dados duplicados.