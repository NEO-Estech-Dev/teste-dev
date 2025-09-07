# Rota de Métricas - Pokemon API

## 🎯 **Nova Rota Criada: `/api/metrics`**

Criei uma rota completa para análise de métricas dos pokémons com todos os parâmetros solicitados.

## 📋 **Endpoints Disponíveis**

### **1. Métricas Disponíveis**
```
GET /api/metrics/available
```
Retorna todas as métricas e atributos disponíveis.

### **2. Análise de Métricas**
```
GET /api/metrics
```

## 🔧 **Parâmetros da Rota de Métricas**

Todos os parâmetros são **opcionais**:

| Parâmetro | Descrição | Padrão | Valores Válidos |
|-----------|-----------|--------|-----------------|
| `metric` | Métrica para analisar | `total_stats` | hp, attack, defense, special_attack, special_defense, speed, total_stats, height, weight, order, base_experience |
| `limit` | Limite de itens | `10` | Qualquer número inteiro |
| `order` | Ordenação | `desc` | `desc` (melhores), `asc` (piores) |
| `attribute` | Atributo específico | `name` | name, pokemon_id, height, weight, base_experience, hp, attack, defense, special_attack, special_defense, speed, total_stats |

## 📊 **Métricas Disponíveis**

### **Stats de Batalha**
- `hp` - Hit Points (HP)
- `attack` - Attack Power
- `defense` - Defense Power
- `special_attack` - Special Attack Power
- `special_defense` - Special Defense Power
- `speed` - Speed
- `total_stats` - Total Stats (soma de todos os stats)

### **Características Físicas**
- `height` - Altura
- `weight` - Peso
- `order` - Ordem no Pokédex
- `base_experience` - Experiência Base

## 🎯 **Exemplos de Uso**

### **1. Top 5 Pokemon com Maior Total de Stats**
```bash
curl "http://localhost:8000/api/metrics?metric=total_stats&limit=5&order=desc"
```

### **2. Top 3 Pokemon com Maior Ataque**
```bash
curl "http://localhost:8000/api/metrics?metric=attack&limit=3&order=desc"
```

### **3. Top 3 Pokemon com Menor Velocidade**
```bash
curl "http://localhost:8000/api/metrics?metric=speed&limit=3&order=asc"
```

### **4. Top 5 Pokemon Mais Pesados**
```bash
curl "http://localhost:8000/api/metrics?metric=weight&limit=5&order=desc"
```

### **5. Top 3 Pokemon com Maior HP, mostrando também o Attack**
```bash
curl "http://localhost:8000/api/metrics?metric=hp&limit=3&order=desc&attribute=attack"
```

### **6. Top 10 Pokemon com Maior Defesa Especial**
```bash
curl "http://localhost:8000/api/metrics?metric=special_defense&limit=10&order=desc"
```

## 📋 **Exemplo de Resposta**

```json
{
  "metric": "total_stats",
  "order": "desc",
  "limit": 3,
  "attribute": "name",
  "total_found": 3,
  "data": [
    {
      "pokemon_id": 3,
      "name": "venusaur",
      "metric_value": 525,
      "stats": {
        "hp": 80,
        "attack": 82,
        "defense": 83,
        "special_attack": 100,
        "special_defense": 100,
        "speed": 80
      }
    },
    {
      "pokemon_id": 2,
      "name": "ivysaur",
      "metric_value": 405,
      "stats": {
        "hp": 60,
        "attack": 62,
        "defense": 63,
        "special_attack": 80,
        "special_defense": 80,
        "speed": 60
      }
    }
  ]
}
```

## 🔍 **Funcionalidades Implementadas**

### **✅ Validação de Parâmetros**
- Validação de métricas válidas
- Validação de atributos válidos
- Validação de ordenação (asc/desc)

### **✅ Filtros Inteligentes**
- Filtra apenas pokémons com stats quando necessário
- Suporte a todas as métricas solicitadas

### **✅ Resposta Flexível**
- Mostra apenas o atributo solicitado
- Inclui todos os stats quando métrica é `total_stats`
- Informações de contexto (métrica, ordem, limite)

### **✅ Tratamento de Erros**
- Mensagens de erro claras para parâmetros inválidos
- Códigos de status HTTP apropriados

## 🚀 **Casos de Uso**

### **1. Análise Competitiva**
```bash
# Melhores atacantes
curl "http://localhost:8000/api/metrics?metric=attack&limit=10&order=desc"

# Melhores defensores
curl "http://localhost:8000/api/metrics?metric=defense&limit=10&order=desc"
```

### **2. Análise de Velocidade**
```bash
# Mais rápidos
curl "http://localhost:8000/api/metrics?metric=speed&limit=5&order=desc"

# Mais lentos
curl "http://localhost:8000/api/metrics?metric=speed&limit=5&order=asc"
```

### **3. Análise de Resistência**
```bash
# Maior HP
curl "http://localhost:8000/api/metrics?metric=hp&limit=10&order=desc"

# Maior defesa especial
curl "http://localhost:8000/api/metrics?metric=special_defense&limit=10&order=desc"
```

### **4. Análise de Características Físicas**
```bash
# Mais pesados
curl "http://localhost:8000/api/metrics?metric=weight&limit=5&order=desc"

# Mais altos
curl "http://localhost:8000/api/metrics?metric=height&limit=5&order=desc"
```

## ✅ **Implementação Completa**

- ✅ **Métrica**: Escolha entre 11 métricas diferentes
- ✅ **Limite**: Controle quantos itens retornar
- ✅ **Atributo**: Mostre apenas o atributo específico desejado
- ✅ **Ordenação**: Melhores (desc) ou piores (asc)
- ✅ **Parâmetros Opcionais**: Todos os parâmetros são opcionais
- ✅ **Validação**: Validação completa de todos os parâmetros
- ✅ **Documentação**: Endpoint `/api/metrics/available` para consultar opções

## 🎉 **Rota Pronta para Uso!**

A rota de métricas está **100% funcional** e atende a todos os requisitos solicitados!
