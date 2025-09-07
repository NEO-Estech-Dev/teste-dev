# Solução para Erro SSL no MySQL Workbench

## ❌ **Erro: "SSL connection error: unknown error number"**

Este erro é comum ao conectar ao MySQL via Docker. Aqui estão as soluções:

## 🔧 **Solução 1: Desabilitar SSL no MySQL Workbench**

### **Passo a Passo:**

1. **Abrir MySQL Workbench**
2. **Criar/Editar Conexão**
3. **Na aba "SSL":**
   - Marque **"Use SSL: No"**
   - Ou deixe **"Use SSL: If Available"**

### **Configurações Recomendadas:**
```
Connection Name: Pokemon API Database
Hostname: localhost
Port: 3306
Username: pokemon_user
Password: password
Default Schema: pokemon_db

SSL Tab:
- Use SSL: No
```

## 🔧 **Solução 2: Configurar SSL no Docker**

Atualizei o docker-compose.yml para desabilitar SSL no MySQL:

```yaml
db:
  image: mysql:8.0
  container_name: pokemon-api-db
  restart: unless-stopped
  environment:
    MYSQL_DATABASE: pokemon_db
    MYSQL_ROOT_PASSWORD: root
    MYSQL_PASSWORD: password
    MYSQL_USER: pokemon_user
  volumes:
    - db_data:/var/lib/mysql
  ports:
    - "3306:3306"
  networks:
    - pokemon-network
  command: --default-authentication-plugin=mysql_native_password --ssl=0
```

## 🔄 **Reiniciar os Containers**

Após atualizar o docker-compose.yml:

```bash
docker-compose down
docker-compose up -d
```

## 🔧 **Solução 3: Configurações Avançadas no MySQL Workbench**

### **Aba Connection:**
```
Connection Name: Pokemon API Database
Connection Method: Standard (TCP/IP)
Hostname: localhost
Port: 3306
Username: pokemon_user
Password: password
Default Schema: pokemon_db
```

### **Aba SSL:**
```
Use SSL: No
```

### **Aba Advanced:**
```
Others: useSSL=false
```

## 🔧 **Solução 4: Testar Conexão via Terminal**

Para verificar se o MySQL está funcionando:

```bash
# Testar conexão
docker-compose exec db mysql -u pokemon_user -ppassword -e "SELECT 1;"

# Verificar configurações SSL
docker-compose exec db mysql -u pokemon_user -ppassword -e "SHOW VARIABLES LIKE '%ssl%';"
```

## 🔧 **Solução 5: Configurações Alternativas**

### **Opção A: Usar IP 127.0.0.1**
```
Hostname: 127.0.0.1
Port: 3306
```

### **Opção B: Usar Root User**
```
Username: root
Password: root
```

### **Opção C: Configurações de Timeout**
Na aba Advanced do MySQL Workbench:
```
connectTimeout=60
readTimeout=60
```

## ✅ **Verificação Final**

Após aplicar as soluções:

1. **Teste a conexão** no MySQL Workbench
2. **Verifique se consegue ver as tabelas**
3. **Execute uma consulta simples:**
   ```sql
   SELECT COUNT(*) FROM pokemon;
   ```

## 🚨 **Se Ainda Não Funcionar**

### **Verificar se o Docker está rodando:**
```bash
docker-compose ps
```

### **Verificar logs do MySQL:**
```bash
docker-compose logs db
```

### **Reiniciar tudo:**
```bash
docker-compose down
docker-compose up -d
```

### **Verificar porta:**
```bash
netstat -an | grep 3306
```

## 🎯 **Solução Mais Comum**

A **Solução 1** (desabilitar SSL no MySQL Workbench) resolve 90% dos casos. 

**Configuração recomendada:**
- Host: `localhost`
- Port: `3306`
- User: `pokemon_user`
- Password: `password`
- SSL: **No**
