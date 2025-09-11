# Arquivos de Deploy para DigitalOcean

Este diretório contém todos os arquivos necessários para fazer o deploy da Pokemon API no DigitalOcean Droplet.

## 📁 Arquivos Incluídos

### Configurações de Produção
- `docker-compose.production.yml` - Docker Compose para produção
- `Dockerfile.production` - Dockerfile otimizado para produção
- `env.production.example` - Exemplo de variáveis de ambiente para produção

### Configurações do Servidor Web
- `docker/nginx/production.conf` - Configuração do Nginx para produção com SSL
- `docker/php/production.ini` - Configurações do PHP para produção

### Scripts de Deploy
- `deploy.sh` - Script principal de deploy automatizado
- `update.sh` - Script para atualizações rápidas

### Documentação
- `DEPLOY_GUIDE.md` - Guia completo de deploy

## 🚀 Como Usar

1. **Primeiro Deploy**:
   ```bash
   chmod +x deploy.sh
   ./deploy.sh
   ```

2. **Atualizações**:
   ```bash
   chmod +x update.sh
   ./update.sh
   ```

## ⚙️ Configurações Importantes

### Antes do Deploy
1. Configure seu domínio no arquivo `docker/nginx/production.conf`
2. Atualize as variáveis de ambiente no arquivo `.env`
3. Configure as credenciais do banco de dados

### Variáveis de Ambiente Críticas
```env
APP_URL=https://seu-dominio.com
DB_PASSWORD=sua_senha_forte
SANCTUM_STATEFUL_DOMAINS=seu-dominio.com,www.seu-dominio.com
```

## 🔒 Segurança

- SSL/TLS configurado automaticamente com Let's Encrypt
- Headers de segurança configurados no Nginx
- Firewall UFW configurado automaticamente
- Permissões de arquivo configuradas corretamente

## 📊 Monitoramento

- Logs centralizados
- Backup automático diário
- Service systemd para auto-restart
- Rotação de logs configurada

## 🆘 Suporte

Consulte o `DEPLOY_GUIDE.md` para instruções detalhadas e troubleshooting.
