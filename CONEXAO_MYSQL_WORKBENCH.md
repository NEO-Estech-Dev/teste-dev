# Como Conectar ao Banco MySQL via MySQL Workbench

## 📋 Informações de Conexão

Com base na configuração do Docker, aqui estão os dados para conectar ao banco:

### **Configurações de Conexão**
- **Host**: `localhost` ou `127.0.0.1`
- **Port**: `3306`
- **Database**: `pokemon_db`
- **Username**: `pokemon_user`
- **Password**: `password`

### **Configurações Alternativas (Root)**
- **Host**: `localhost` ou `127.0.0.1`
- **Port**: `3306`
- **Database**: `pokemon_db`
- **Username**: `root`
- **Password**: `root`

## 🔧 Passo a Passo no MySQL Workbench

### 1. **Abrir MySQL Workbench**
- Abra o MySQL Workbench no seu computador

### 2. **Criar Nova Conexão**
- Clique em **"+"** ao lado de "MySQL Connections"
- Ou vá em **Database > Manage Connections**

### 3. **Configurar Conexão**
Preencha os campos:

```
Connection Name: Pokemon API Database
Connection Method: Standard (TCP/IP)
Hostname: localhost
Port: 3306
Username: pokemon_user
Password: password
Default Schema: pokemon_db
```

### 4. **Testar Conexão**
- Clique em **"Test Connection"**
- Se aparecer "Successfully made the MySQL connection", está funcionando!

### 5. **Conectar**
- Clique em **"OK"** para salvar
- Clique duas vezes na conexão criada para conectar

## 🗄️ Estrutura do Banco

Após conectar, você verá as seguintes tabelas:

### **Tabelas Principais**
- `pokemon` - Dados dos pokémons
- `types` - Tipos de pokémons
- `abilities` - Habilidades dos pokémons

### **Tabelas de Relacionamento**
- `pokemon_types` - Relacionamento pokémon ↔ tipo
- `pokemon_abilities` - Relacionamento pokémon ↔ habilidade

### **Tabelas do Laravel**
- `users` - Usuários do sistema
- `migrations` - Controle de migrations
- `password_resets` - Reset de senhas
- `failed_jobs` - Jobs falhados
- `personal_access_tokens` - Tokens de acesso

## 📊 Consultas Úteis

### **Ver todos os pokémons**
```sql
SELECT * FROM pokemon LIMIT 10;
```

### **Pokémons por tipo**
```sql
SELECT p.name, t.name as type_name 
FROM pokemon p
JOIN pokemon_types pt ON p.id = pt.pokemon_id
JOIN types t ON pt.type_id = t.id
WHERE t.name = 'fire';
```

### **Pokémons com suas habilidades**
```sql
SELECT p.name, a.name as ability_name, pa.is_hidden
FROM pokemon p
JOIN pokemon_abilities pa ON p.id = pa.pokemon_id
JOIN abilities a ON pa.ability_id = a.id
WHERE p.name = 'pikachu';
```

### **Estatísticas por tipo**
```sql
SELECT t.name, COUNT(pt.pokemon_id) as pokemon_count
FROM types t
LEFT JOIN pokemon_types pt ON t.id = pt.type_id
GROUP BY t.id, t.name
ORDER BY pokemon_count DESC;
```

## 🔍 Verificações Importantes

### **1. Verificar se o Docker está rodando**
```bash
docker-compose ps
```

### **2. Verificar se a porta está aberta**
```bash
netstat -an | grep 3306
```

### **3. Testar conexão via terminal**
```bash
docker-compose exec db mysql -u pokemon_user -p pokemon_db
# Digite a senha: password
```

## ⚠️ Solução de Problemas

### **Erro: "Can't connect to MySQL server"**
- Verifique se o Docker está rodando: `docker-compose ps`
- Verifique se a porta 3306 está livre
- Reinicie os containers: `docker-compose restart`

### **Erro: "Access denied for user"**
- Verifique se está usando as credenciais corretas
- Tente conectar como root: username `root`, password `root`

### **Erro: "Unknown database"**
- Verifique se o banco `pokemon_db` existe
- Execute as migrations: `docker-compose exec app php artisan migrate`

## 🎯 Pronto!

Após seguir esses passos, você terá acesso completo ao banco de dados da API Pokemon via MySQL Workbench, podendo visualizar e consultar todos os dados importados da PokéAPI.
