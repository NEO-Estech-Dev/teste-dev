**# Teste Técnico – Tutorial Instalação e Uso**

1 - Configuração do ambiete docker

    * docker compose up --build

2 - Preparação das migrations

    * php artisan migrate --seed

3 - Importação dos dados da PokeAPI (execute em terminais diferentes)

    * php artisan queue:work
    * php artisan pokemon:sincronizar (comando adicionado)
     
4 - Os endpoints inseridos foram:

    1 - /sessao/login.
    2 - /sessao/cadastrar.
    3 - /pokemon (indexMetricas - Requer autenticação).

5 - Sobre os endpoints inseridos:

    1 - login - payload:
        * email: teste@gmail.com
        * password: 12345678
    
    2 - cadastrar - payload:
        * name:"Usuario Teste",
        * email:"teste@gmail.com",
        * password:"12345678"
    
    3 - pokemon payload(indexMetricas) - payload:
        * page: 1,
        * limit: 10,
        * fields: name,height...  (name, height, weight, order, specie, base_experience)
        * metric: height (height, weight, order, specie e base_experience)
        * order: asc (asc ou desc)
        

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

