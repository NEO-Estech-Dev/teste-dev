# 🧪 Teste Técnico – Desenvolvedor PHP / N8N (Estech)

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
- Permitir reexecução do comando sem corromper os dados (idempotência é um diferencial).

---

### 2️⃣ Endpoint de Métricas

Criar uma **rota HTTP** que permita consultar métricas dos Pokémons armazenados.

A rota deve permitir, **de forma opcional**, os seguintes parâmetros:

- **Métrica a ser analisada**, por exemplo:
  - `hp`
  - `attack`
  - `defense`
  - `special_attack`
  - `special_defense`
  - `speed`
  - `total`
  - `height`
  - `weight`
  - `order`

- **Limite de resultados**
  - Exemplo: top 5, top 10, etc.

- **Campo específico a ser retornado**
  - Exemplo: retornar apenas o `name` no ranking.

- **Ordenação**
  - Maiores valores (melhores)
  - Menores valores (piores)

📌 **Observação:**  
Todos os parâmetros devem ser **opcionais** e possuir valores padrão coerentes.

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

