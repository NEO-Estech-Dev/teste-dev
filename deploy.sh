#!/bin/bash

# Pokemon API Deployment Script for DigitalOcean Droplet
# This script automates the deployment process

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="pokemon-api"
PROJECT_DIR="/var/www/$PROJECT_NAME"
BACKUP_DIR="/var/backups/$PROJECT_NAME"
DOMAIN="your-domain.com"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root"
        exit 1
    fi
}

# Function to install dependencies
install_dependencies() {
    print_status "Installing system dependencies..."
    
    apt-get update
    apt-get install -y \
        apt-transport-https \
        ca-certificates \
        curl \
        gnupg \
        lsb-release \
        git \
        unzip \
        software-properties-common
    
    # Install Docker
    if ! command -v docker &> /dev/null; then
        print_status "Installing Docker..."
        curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
        echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
        apt-get update
        apt-get install -y docker-ce docker-ce-cli containerd.io
        systemctl enable docker
        systemctl start docker
    fi
    
    # Install Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        print_status "Installing Docker Compose..."
        curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
        chmod +x /usr/local/bin/docker-compose
    fi
    
    print_success "Dependencies installed successfully"
}

# Function to setup project directory
setup_project() {
    print_status "Setting up project directory..."
    
    mkdir -p $PROJECT_DIR
    mkdir -p $BACKUP_DIR
    mkdir -p $PROJECT_DIR/ssl
    
    # Set proper permissions
    chown -R www-data:www-data $PROJECT_DIR
    chmod -R 755 $PROJECT_DIR
    
    print_success "Project directory setup completed"
}

# Function to clone or update repository
setup_repository() {
    print_status "Setting up repository..."
    
    if [ -d "$PROJECT_DIR/.git" ]; then
        print_status "Updating existing repository..."
        cd $PROJECT_DIR
        git pull origin main
    else
        print_status "Cloning repository..."
        git clone https://github.com/your-username/pokemon-api.git $PROJECT_DIR
        cd $PROJECT_DIR
    fi
    
    print_success "Repository setup completed"
}

# Function to setup environment
setup_environment() {
    print_status "Setting up environment configuration..."
    
    cd $PROJECT_DIR
    
    if [ ! -f ".env" ]; then
        cp env.production.example .env
        print_warning "Please edit .env file with your production settings"
        print_warning "Don't forget to generate APP_KEY with: php artisan key:generate"
    fi
    
    print_success "Environment setup completed"
}

# Function to setup SSL certificates
setup_ssl() {
    print_status "Setting up SSL certificates..."
    
    cd $PROJECT_DIR
    
    # Install Certbot for Let's Encrypt
    if ! command -v certbot &> /dev/null; then
        apt-get install -y certbot python3-certbot-nginx
    fi
    
    # Generate SSL certificate
    if [ ! -f "ssl/cert.pem" ]; then
        print_status "Generating SSL certificate with Let's Encrypt..."
        certbot certonly --standalone -d $DOMAIN -d www.$DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN
        
        # Copy certificates to project directory
        cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem ssl/cert.pem
        cp /etc/letsencrypt/live/$DOMAIN/privkey.pem ssl/key.pem
        
        # Set proper permissions
        chown -R www-data:www-data ssl/
        chmod 600 ssl/key.pem
        chmod 644 ssl/cert.pem
    fi
    
    print_success "SSL certificates setup completed"
}

# Function to build and start containers
deploy_application() {
    print_status "Building and starting application containers..."
    
    cd $PROJECT_DIR
    
    # Stop existing containers
    docker-compose -f docker-compose.production.yml down || true
    
    # Build and start containers
    docker-compose -f docker-compose.production.yml up -d --build
    
    # Wait for database to be ready
    print_status "Waiting for database to be ready..."
    sleep 30
    
    # Run Laravel setup commands
    print_status "Running Laravel setup commands..."
    docker-compose -f docker-compose.production.yml exec app php artisan key:generate --force
    docker-compose -f docker-compose.production.yml exec app php artisan migrate --force
    docker-compose -f docker-compose.production.yml exec app php artisan db:seed --force
    docker-compose -f docker-compose.production.yml exec app php artisan config:cache
    docker-compose -f docker-compose.production.yml exec app php artisan route:cache
    docker-compose -f docker-compose.production.yml exec app php artisan view:cache
    
    print_success "Application deployed successfully"
}

# Function to setup firewall
setup_firewall() {
    print_status "Setting up firewall..."
    
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw --force enable
    
    print_success "Firewall configured"
}

# Function to setup monitoring
setup_monitoring() {
    print_status "Setting up basic monitoring..."
    
    # Create log rotation
    cat > /etc/logrotate.d/pokemon-api << EOF
$PROJECT_DIR/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 644 www-data www-data
}
EOF
    
    # Create systemd service for auto-restart
    cat > /etc/systemd/system/pokemon-api.service << EOF
[Unit]
Description=Pokemon API Docker Compose
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=$PROJECT_DIR
ExecStart=/usr/local/bin/docker-compose -f docker-compose.production.yml up -d
ExecStop=/usr/local/bin/docker-compose -f docker-compose.production.yml down
TimeoutStartSec=0

[Install]
WantedBy=multi-user.target
EOF
    
    systemctl enable pokemon-api.service
    
    print_success "Monitoring setup completed"
}

# Function to create backup script
create_backup_script() {
    print_status "Creating backup script..."
    
    cat > /usr/local/bin/backup-pokemon-api.sh << 'EOF'
#!/bin/bash

BACKUP_DIR="/var/backups/pokemon-api"
PROJECT_DIR="/var/www/pokemon-api"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup database
docker-compose -f $PROJECT_DIR/docker-compose.production.yml exec -T db mysqldump -u pokemon_user -ppassword pokemon_db > $BACKUP_DIR/database_$DATE.sql

# Backup application files
tar -czf $BACKUP_DIR/app_$DATE.tar.gz -C $PROJECT_DIR .

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
EOF
    
    chmod +x /usr/local/bin/backup-pokemon-api.sh
    
    # Add to crontab for daily backups
    (crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-pokemon-api.sh") | crontab -
    
    print_success "Backup script created"
}

# Main deployment function
main() {
    print_status "Starting Pokemon API deployment..."
    
    check_root
    install_dependencies
    setup_project
    setup_repository
    setup_environment
    setup_ssl
    deploy_application
    setup_firewall
    setup_monitoring
    create_backup_script
    
    print_success "Deployment completed successfully!"
    print_status "Your API should be available at: https://$DOMAIN"
    print_warning "Don't forget to:"
    print_warning "1. Update DNS records to point to this server"
    print_warning "2. Edit .env file with your production settings"
    print_warning "3. Update domain in nginx configuration"
    print_warning "4. Test your API endpoints"
}

# Run main function
main "$@"
