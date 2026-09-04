# Especificação — Documentação OpenAPI da API

## 1. Objetivo

Adicionar documentação OpenAPI 3.1 gerada a partir do código da aplicação. A documentação deve descrever integralmente o endpoint existente de ranking de Pokémon e incluir automaticamente novos endpoints adicionados futuramente sob o prefixo `/api`.

O código Laravel é a fonte de verdade. Rotas, Form Requests, enums, tipos de retorno e API Resources devem alimentar a geração da especificação, evitando manter manualmente um segundo contrato completo em YAML ou JSON.

Esta especificação cobre:

- instalação e configuração do gerador OpenAPI;
- interface navegável da documentação;
- documento OpenAPI em JSON;
- contrato documentado do endpoint atual;
- convenções para que endpoints futuros sejam descobertos.

Não fazem parte da documentação pública as rotas web, comandos Artisan, endpoints internos da integração com a PokeAPI ou as próprias rotas usadas para servir a documentação.

## 2. Decisão técnica

Usar o package `dedoc/scramble` na série estável compatível `^0.13`.

O Scramble deve ser preferido porque analisa as convenções que o projeto já utiliza:

- rotas Laravel;
- controllers e seus tipos de retorno;
- `FormRequest::rules()`;
- regras `Rule::in()` usadas com os enums do ranking;
- Eloquent Models;
- API Resources e coleções paginadas.

Essa abordagem mantém os valores aceitos próximos à validação real e reduz a possibilidade de divergência entre implementação e documentação. Atributos ou anotações específicas do Scramble só devem ser usados para informações que não possam ser inferidas, como descrições, exemplos, valores padrão ou o formato dinâmico da resposta do ranking. Não duplicar todos os schemas com atributos do `swagger-php`.

O package deve ser uma dependência normal da aplicação, e não somente uma dependência de desenvolvimento, pois registra as rotas que servem a documentação e permite disponibilizá-las de forma controlada em outros ambientes no futuro.

Comandos de instalação:

```bash
composer require dedoc/scramble:^0.13 --no-interaction
php artisan vendor:publish --provider="Dedoc\Scramble\ScrambleServiceProvider" --tag="scramble-config" --no-interaction
```

O `composer.lock` deve registrar a versão efetivamente instalada. Nenhum segundo gerador OpenAPI deve ser adicionado.

## 3. Visão geral do fluxo

```text
routes/api.php
      │
      ├── controllers tipados
      ├── Form Requests + regras + enums
      └── API Resources + paginators
                    │
                    ▼
              dedoc/scramble
                    │
          ┌─────────┴─────────┐
          ▼                   ▼
     GET /docs/api     GET /docs/api.json
      interface          OpenAPI 3.1 JSON
```

O Scramble deve gerar o documento no momento da leitura. Arquivos exportados são artefatos derivados e não substituem o código como fonte de verdade.

## 4. Configuração

Publicar `config/scramble.php` para tornar explícita a seleção de rotas e os metadados da API. Manter as configurações do package que não precisarem ser personalizadas.

Configuração mínima esperada:

```php
return [
    'api_path' => 'api',
    'api_domain' => null,

    'info' => [
        'version' => '1.0.0',
        'description' => 'API versionada para consultar rankings de Pokémon.',
    ],

    'servers' => null,

    // Demais opções publicadas pelo package.
];
```

`api_path` deve permanecer como `api`, e não `api/v1`. Dessa forma, a documentação atual inclui a versão `v1` e versões ou recursos futuros continuam sendo descobertos sem uma lista manual de controllers.

Com um único prefixo estático `api`, o servidor OpenAPI deve apontar para `/api` e os paths documentados devem ser relativos a essa base. Portanto, o endpoint atual deve aparecer como `/v1/pokemons/ranking` no objeto `paths`, representando a URL pública completa `/api/v1/pokemons/ranking`.

Não definir `servers` com host local fixo. Com `null`, o package deve derivar esquema, host e porta da aplicação em execução. Uma URL absoluta de produção só deverá ser adicionada quando houver um domínio de produção definido.

O documento deve possuir:

| Campo | Valor esperado |
| --- | --- |
| `openapi` | `3.1.0` |
| `info.version` | `1.0.0` |
| `info.description` | descrição não vazia da finalidade da API |
| `servers` | servidor calculado para o prefixo `/api` |
| `paths` | todas as rotas HTTP da aplicação sob `/api` |

## 5. Exposição da documentação

Expor somente as rotas padrão do Scramble:

| Método e caminho | Finalidade |
| --- | --- |
| `GET /docs/api` | Interface navegável da documentação |
| `GET /docs/api.json` | Especificação OpenAPI 3.1 em JSON |

Não registrar uma API nomeada nem caminhos alternativos para a interface ou para o documento JSON. As duas rotas devem usar a proteção `RestrictedDocsAccess` fornecida pelo package. Nesta entrega, a documentação fica acessível somente no ambiente `local`, que é o comportamento seguro padrão do Scramble.

Não criar um `viewApiDocs` gate que libere acesso público incondicional. Se a documentação precisar ser exposta em staging ou produção futuramente, deverá ser criado um gate autenticado com uma regra explícita de autorização. A UI e o JSON devem receber a mesma proteção, pois o documento pode revelar parâmetros, schemas e mecanismos de autenticação da API.

O recurso interativo de executar requisições pode permanecer com a configuração padrão enquanto a API for pública. Quando endpoints autenticados forem adicionados, a estratégia de segurança e o envio de credenciais deverão ser revistos antes de habilitar esses endpoints no testador interativo.

## 6. Endpoint de ranking

A operação existente deve ser documentada a partir de `PokemonRankingController`, `PokemonRankingRequest`, `RankedPokemonResource` e dos enums relacionados.

### 6.1 Operação

```http
GET /api/v1/pokemons/ranking
Accept: application/json
```

O documento deve apresentar:

- um resumo que identifique a consulta de ranking de Pokémon;
- uma descrição informando que os dados são locais, paginados e ordenados pela métrica solicitada;
- um `operationId` único e estável;
- conteúdo de sucesso em `application/json`;
- ausência de requisito de autenticação nesta versão.

O endpoint não deve ser descrito como responsável por consultar ou iniciar a ingestão da PokeAPI.

### 6.2 Parâmetros de query

Todos os parâmetros devem ser documentados em `parameters`, com `in: query` e `required: false`:

| Parâmetro | Schema | Valores ou limites | Padrão |
| --- | --- | --- | --- |
| `metric` | `string` | `hp`, `attack`, `defense`, `special_attack`, `special_defense`, `speed`, `height`, `weight`, `base_experience` | `hp` |
| `field` | `string` | `pokeapi_id`, `name`, `height`, `weight`, `base_experience`, `hp`, `attack`, `defense`, `special_attack`, `special_defense`, `speed` | `name` |
| `order` | `string` | `desc`, `asc` | `desc` |
| `page` | `integer` | mínimo `1` | `1` |
| `per_page` | `integer` | mínimo `1`, máximo `100` | `10` |

Os enums devem continuar sendo inferidos de `Rule::in()` e das classes enum já existentes. Não copiar essas listas para uma estrutura paralela usada somente pela documentação.

Como os valores padrão são aplicados em `prepareForValidation()`, adicionar metadados mínimos suportados pelo Scramble caso eles não apareçam automaticamente no documento gerado. Descrições, `@default` e `@example` podem ficar junto às regras correspondentes do Form Request, sem reproduzir as regras de validação.

### 6.3 Resposta `200`

A resposta deve representar o envelope real do paginator do Laravel:

```json
{
    "data": [
        {
            "name": "blissey"
        }
    ],
    "links": {
        "first": "http://localhost/api/v1/pokemons/ranking?page=1",
        "last": "http://localhost/api/v1/pokemons/ranking?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "links": [],
        "path": "http://localhost/api/v1/pokemons/ranking",
        "per_page": 10,
        "to": 1,
        "total": 1,
        "metric": "hp",
        "field": "name",
        "order": "desc"
    }
}
```

Cada item de `data` contém exatamente o campo selecionado pelo parâmetro `field`. O schema não deve afirmar que todos os atributos do model `Pokemon` são retornados simultaneamente.

As variantes possíveis do item são:

| Campo selecionado | Tipo do valor |
| --- | --- |
| `name` | `string` |
| `base_experience` | `integer` ou `null` |
| `pokeapi_id`, `height`, `weight`, `hp`, `attack`, `defense`, `special_attack`, `special_defense`, `speed` | `integer` |

Essa característica dinâmica deve ser expressa por variantes `oneOf` com um único campo conhecido e `additionalProperties: false`, ou por outra composição OpenAPI 3.1 semanticamente equivalente. A descrição deve deixar claro que a variante retornada é determinada por `field`.

O Scramble deve primeiro inferir o máximo possível do `AnonymousResourceCollection`, do paginator e de `additional()`. Como `RankedPokemonResource::toArray()` atualmente delega para `attributesToArray()`, sua inferência deve ser conferida. Se o schema gerado representar o model inteiro ou um objeto genérico, corrigir somente esse ponto por meio de uma transformação de operação suportada pelo Scramble ou tornando o contrato do Resource explícito. Não manter uma cópia manual do envelope completo se o package já o infere corretamente.

Os objetos `links` e `meta` devem incluir tipos e nulabilidade coerentes com respostas de páginas vazias e não vazias. Os campos adicionais `metric`, `field` e `order` em `meta` são obrigatórios e devem possuir os mesmos enums usados na requisição.

### 6.4 Resposta `422`

Documentar `422 Unprocessable Content` para falhas de validação, com o envelope JSON padrão do Laravel:

```json
{
    "message": "The metric field is invalid.",
    "errors": {
        "metric": [
            "The selected metric is invalid."
        ]
    }
}
```

O schema deve permitir qualquer um dos cinco parâmetros validados como chave de `errors`, contendo uma lista de mensagens. O exemplo é ilustrativo e não deve fixar a tradução da aplicação como parte do contrato.

## 7. Convenções para endpoints futuros

Todo novo endpoint público deve ser criado sob `routes/api.php` ou possuir URI iniciada pelo segmento completo `api`. Ele será incluído automaticamente porque `scramble.api_path` cobre esse prefixo.

Para manter a geração confiável, endpoints futuros devem:

1. Usar controllers com parâmetros e retornos tipados.
2. Usar Form Requests para validação, mantendo regras, enums e limites como fonte de verdade.
3. Retornar API Resources para contratos Eloquent ou estruturas explicitamente tipadas para outros payloads.
4. Usar status HTTP e tipos de resposta coerentes com o comportamento real.
5. Adicionar apenas descrições, exemplos ou ajustes que o Scramble não consiga inferir.
6. Não editar manualmente `/docs/api.json`, pois ele é sempre um resultado da geração.

Novas versões, como `/api/v2`, devem permanecer no mesmo documento enquanto houver uma única API pública. Documentos separados por versão só devem ser introduzidos quando existir uma necessidade concreta de publicação, autorização ou ciclo de vida independente.

Caso surja uma rota técnica sob `/api` que não faça parte do contrato público, ela deverá ser explicitamente excluída pela configuração `api_path.exclude` ou pelo mecanismo de exclusão do Scramble. A exclusão deve ser deliberada.

## 8. Exportação

A especificação deve poder ser exportada sem iniciar um servidor HTTP:

```bash
php artisan scramble:export
php artisan scramble:export --path=/tmp/pokemon-api-openapi.json
```

O comando sem `--path` usa o caminho definido em `config/scramble.php`. O artefato exportado não deve ser commitado por padrão, pois seria uma cópia derivada sujeita a ficar desatualizada. Se um consumidor externo exigir um arquivo versionado, essa decisão deverá vir acompanhada de uma verificação de diff no CI.

Se a documentação vier a ser servida em staging ou produção, o deploy deverá executar `php artisan scramble:cache` depois da nova versão estar instalada. O cache precisa ser recriado a cada deploy para não servir uma especificação da versão anterior.

## 9. Critérios de aceite

- `dedoc/scramble` está instalado como dependência normal e é compatível com Laravel 13.
- `config/scramble.php` existe e seleciona todas as rotas sob o prefixo `api`.
- `/docs/api` apresenta a interface navegável no ambiente local.
- `/docs/api.json` retorna um documento OpenAPI 3.1 válido no ambiente local.
- Somente as duas rotas padrão do Scramble servem a documentação, sem caminhos alternativos.
- A documentação não fica publicamente acessível em produção sem um gate explícito.
- O endpoint `/api/v1/pokemons/ranking` está descrito com parâmetros, defaults, enums, paginação, resposta dinâmica e erro de validação coerentes com a implementação.
- O schema de cada item do ranking não expõe colunas que a resposta real não retorna.
- Novos endpoints sob `/api` são descobertos automaticamente sem exigir uma lista manual de rotas.
- `php artisan scramble:export` conclui com sucesso.
- O Pint, o PHPStan e as demais verificações de saúde aplicáveis ao projeto passam após a implementação.
