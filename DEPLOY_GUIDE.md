# Deploy da Pokemon API no DigitalOcean Droplet

Este guia fornece instruções completas para fazer o deploy da Pokemon API Laravel em um Droplet do DigitalOcean usando Docker.

## 📋 Pré-requisitos

- Conta no DigitalOcean
- Domínio configurado (opcional, mas recomendado)
- Acesso SSH ao servidor

## 🚀 Passo a Passo do Deploy

### 1. Criar Droplet no DigitalOcean

1. Acesse o [DigitalOcean](https://cloud.digitalocean.com/)
2. Clique em "Create" → "Droplets"
3. Escolha as configurações:
   - **Imagem**: Ubuntu 22.04 LTS
   - **Plano**: Basic (mínimo 2GB RAM recomendado)
   - **Região**: Escolha a mais próxima dos seus usuários
   - **Autenticação**: SSH Key (recomendado) ou Password
4. Clique em "Create Droplet"

### 2. Conectar ao Droplet

```bash
ssh root@SEU_IP_DO_DROPLET
```

### 3. Preparar o Servidor

Execute os seguintes comandos no servidor:

```bash
# Atualizar sistema
apt update && apt upgrade -y

# Instalar dependências básicas
apt install -y git curl wget unzip

# Clonar o repositório (substitua pela URL do seu repositório)
git clone https://github.com/seu-usuario/pokemon-api.git /var/www/pokemon-api

# Entrar no diretório do projeto
cd /var/www/pokemon-api

# Tornar o script de deploy executável
chmod +x deploy.sh
```

### 4. Configurar Variáveis de Ambiente

Edite o arquivo `.env` com suas configurações de produção:

```bash
nano .env
```

Configure as seguintes variáveis importantes:

```env
APP_NAME="Pokemon API"
APP_ENV=production
APP_KEY=base64:SUA_CHAVE_AQUI
APP_DEBUG=false
APP_URL=https://seu-dominio.com

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=pokemon_db
DB_USERNAME=pokemon_user
DB_PASSWORD=SUA_SENHA_FORTE_AQUI

SANCTUM_STATEFUL_DOMAINS=seu-dominio.com,www.seu-dominio.com
```

### 5. Executar o Deploy

Execute o script de deploy automatizado:

```bash
./deploy.sh
```

O script irá:
- Instalar Docker e Docker Compose
- Configurar SSL com Let's Encrypt
- Construir e iniciar os containers
- Configurar firewall
- Configurar monitoramento
- Criar scripts de backup

### 6. Configurar DNS (se usando domínio)

Se você está usando um domínio personalizado:

1. Acesse o painel do seu provedor de DNS
2. Configure os registros:
   - **A**: `@` → IP do seu Droplet
   - **A**: `www` → IP do seu Droplet

### 7. Verificar o Deploy

Após o deploy, teste se tudo está funcionando:

```bash
# Verificar status dos containers
docker-compose -f docker-compose.production.yml ps

# Verificar logs
docker-compose -f docker-compose.production.yml logs

# Testar API
curl https://seu-dominio.com/api/metrics
```

## 🔧 Configurações Adicionais

### Configurar Backup Automático

O script já configura backups diários, mas você pode ajustar:

```bash
# Editar crontab
crontab -e

# Adicionar linha para backup diário às 2h
0 2 * * * /usr/local/bin/backup-pokemon-api.sh
```

### Monitoramento

Para monitorar a aplicação:

```bash
# Ver logs em tempo real
docker-compose -f docker-compose.production.yml logs -f

# Verificar uso de recursos
docker stats

# Verificar espaço em disco
df -h
```

### Atualizações

Para atualizar a aplicação:

```bash
cd /var/www/pokemon-api
./update.sh
```

## 🔒 Segurança

### Firewall

O script configura automaticamente o UFW com as portas necessárias:
- 22 (SSH)
- 80 (HTTP)
- 443 (HTTPS)

### SSL/TLS

O SSL é configurado automaticamente com Let's Encrypt e renova automaticamente.

### Headers de Segurança

O Nginx está configurado com headers de segurança:
- X-Frame-Options
- X-XSS-Protection
- X-Content-Type-Options
- Strict-Transport-Security
- Content-Security-Policy

## 📊 Endpoints da API

Após o deploy, sua API estará disponível em:

- **Base URL**: `https://seu-dominio.com/api`
- **Métricas**: `GET /api/metrics`
- **Pokemon**: `GET /api/pokemon`
- **Tipos**: `GET /api/types`
- **Habilidades**: `GET /api/abilities`
- **Autenticação**: `POST /api/auth/login`

## 🐛 Troubleshooting

### Problemas Comuns

1. **Erro de permissão**:
   ```bash
   chown -R www-data:www-data /var/www/pokemon-api
   ```

2. **Container não inicia**:
   ```bash
   docker-compose -f docker-compose.production.yml logs app
   ```

3. **Erro de SSL**:
   ```bash
   certbot renew --dry-run
   ```

4. **Banco de dados não conecta**:
   ```bash
   docker-compose -f docker-compose.production.yml exec db mysql -u pokemon_user -p
   ```

### Logs Importantes

- **Aplicação**: `/var/www/pokemon-api/storage/logs/`
- **Nginx**: `/var/log/nginx/`
- **Docker**: `docker-compose logs`

## 💰 Custos Estimados

- **Droplet Basic (2GB RAM)**: ~$12/mês
- **Domínio**: ~$10-15/ano
- **SSL**: Gratuito (Let's Encrypt)

## 📞 Suporte

Se encontrar problemas:

1. Verifique os logs
2. Confirme as configurações do `.env`
3. Teste a conectividade de rede
4. Verifique se todos os containers estão rodando

## 🔄 Manutenção

### Atualizações Regulares

```bash
# Atualizar sistema
apt update && apt upgrade -y

# Atualizar aplicação
cd /var/www/pokemon-api
git pull origin main
./update.sh
```

### Backup Manual

```bash
/usr/local/bin/backup-pokemon-api.sh
```

### Monitoramento de Recursos

```bash
# CPU e Memória
htop

# Espaço em disco
df -h

# Uso de rede
iftop
```

---

**🎉 Parabéns! Sua Pokemon API está agora rodando em produção no DigitalOcean!**
