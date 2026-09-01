# 🧪 Teste Técnico – Desenvolvedor PHP (Estech)

Bem-vindo à resolução do desafio técnico! Esta aplicação foi desenvolvida com foco extremo em **Performance, Escalabilidade e Boas Práticas**, atendendo a 100% dos requisitos obrigatórios e também a 100% dos requisitos bônus.
Bem-vindo à resolução do desafio técnico! Esta aplicação foi desenvolvida com foco em **Performance, Escalabilidade e Boas Práticas**, buscando atender a todos os requisitos obrigatórios e também aos desafios bônus propostos.

---

## 🚀 Como subir o ambiente (Docker)

O ambiente de desenvolvimento foi orquestrado utilizando o **Laravel Sail** (Docker), o que garante uma execução limpa e padronizada.

**1. Instale as dependências do Composer**
Como o container do Sail ainda não está rodando, utilize o composer local ou o container temporário do próprio Sail para instalar as dependências:
```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
```
*(Se você já tiver o composer local na máquina, basta rodar `composer install`)*

**2. Configure as variáveis de ambiente**
```bash
cp .env.example .env
```
Nenhuma alteração extra é necessária no `.env`, pois ele já está configurado para o banco de dados do Docker (`DB_HOST=mysql`, etc).

**3. Suba os containers**
```bash
./vendor/bin/sail up -d
```

**4. Gere a chave da aplicação**
```bash
./vendor/bin/sail artisan key:generate
```

---

## 🗄️ Banco de Dados e Usuário Admin

Com os containers rodando, execute as migrations e os seeders.
O Seeder já está configurado para criar o usuário padrão necessário para autenticação na API.

```bash
./vendor/bin/sail artisan migrate --seed
```

**Credenciais de Acesso (Geradas pelo Seeder):**
- **Email:** `admin@estech.com`
- **Senha:** `password`

---

## ⚡ Ingestão de Dados (PokeAPI)

Para popular o banco de dados, rode o Command criado exclusivamente para esta tarefa.

```bash
./vendor/bin/sail artisan app:ingest-pokemons --limit=150
```
> **Nota de Performance:** Este command não faz as requisições de forma sequencial (o que seria muito lento). Ele utiliza o `Http::pool` do Laravel para disparar lotes assíncronos (chunks) contra a PokeAPI, processando os dados de forma muito rápida. Além disso, a inserção no banco de dados é feita através de um único `upsert` em lote, economizando processamento e conexões com o banco.

---

## 📡 Endpoints da API

Para facilitar a sua validação, os testes da API requerem o cabeçalho `Accept: application/json`.

### 1. Login (Sanctum)
- **POST** `http://localhost/api/login`
- **Body (JSON):**
```json
{
    "email": "admin@estech.com",
    "password": "password"
}
```
*Retornará o Bearer Token que deve ser usado no Header das próximas requisições (`Authorization: Bearer <token>`).*

### 2. Consulta de Métricas de Pokémons
- **GET** `http://localhost/api/metrics/pokemons`
- **Headers:** `Authorization: Bearer <token>`
- **Parâmetros da URL (Query Params - 100% opcionais):**
  - `metric` (Padrão: `hp` | Permitidos: `hp, attack, defense, special_attack, special_defense, speed`)
  - `sort` (Padrão: `desc` | Permitidos: `asc, desc`)
  - `field` (Opcional | Retorna apenas o campo desejado do ranking)
  - `per_page` (Padrão: `15` | Limite máximo configurado para paginação)

**Exemplo de uso:** `http://localhost/api/metrics/pokemons?metric=attack&sort=desc&field=name`

### 3. Logout
- **POST** `http://localhost/api/logout`
- **Headers:** `Authorization: Bearer <token>`
*Invalida e revoga a chave da sessão atual por questões de segurança.*

---

## 🏆 Decisões Arquiteturais e Destaques (Bônus)

- **Laravel Octane (FrankenPHP vs Swoole):** O requisito bônus sugere o uso de Swoole. Optei por utilizar o **FrankenPHP**, que é o novo servidor padrão e recomendado pelo Laravel 11. Ele entrega performance assíncrona semelhante ou superior ao Swoole, porém com uma melhor experiência de desenvolvimento (não exige compilação de extensões C no host).
- **Camada de Cache na API:** O endpoint de métricas utiliza `Cache::remember`. O Command de Ingestão realiza automaticamente o `Cache::flush()` ao concluir o upsert. Isso significa tempo de resposta em milissegundos para a API, batendo no banco de dados apenas na primeira requisição após uma sincronização.
- **Segurança Ant-SQL Injection:** Foi criado o `PokemonMetricRequest` que utiliza validação `in:` para garantir que o usuário não consiga enviar nomes de colunas maliciosas para as cláusulas de ordenação (`orderBy`) do banco.
- **Índices no Banco de Dados:** Todas as métricas (`hp`, `attack`, etc) foram indexadas na `migration`, permitindo que o MySQL resolva as queries de ordenação através de B-Trees instantaneamente, sem realizar *table scans*.
- **Testes Automatizados (Pest/PHPUnit):** Foram criados testes de funcionalidade (Feature) abrangendo o isolamento de rotas pelo Sanctum e o comportamento do Command, incluindo o **Mock (`Http::fake`)** da PokeAPI para garantir que a suíte de testes rode de forma isolada, determinística e sem dependência de internet. Para rodar:
  ```bash
  ./vendor/bin/sail artisan test
  ```

---

## 🎁 Bônus Extra: Collection do Insomnia/Postman
Para auxiliar no teste manual dos endpoints, disponibilizei um arquivo chamado `insomnia_collection.json` na raiz do projeto. 
Ele contém as requisições previamente configuradas (Login, Logout e exemplos das variações de consultas de métricas) e pode ser importado diretamente no Insomnia ou Postman.
