# Hetzner Server Provisioning Guide for EG Construction

## Recommended Server Configuration

| Setting | Recommendation | Notes |
|---------|---------------|-------|
| **Type** | Regular Performance | Better for web apps, newer hardware |
| **Size** | CX21 or CX31 | See comparison below |
| **Location** | Helsinki (eu-central) | Or choose closer to your users |
| **Image** | Ubuntu 24.04 | Best PHP/Laravel support |
| **Architecture** | x86 (AMD) | Standard, widest compatibility |
| **Networking** | Public IPv4 + IPv6 | Both enabled |
| **SSH Key** | Required | More secure than password |
| **Backups** | Enabled | ~20% extra cost, worth it |
| **Name** | `eg-construction-prod` | Or your preferred name |

---

## Server Size Comparison

| Plan | vCPU | RAM | Storage | Traffic | Price/mo |
|------|------|-----|---------|---------|----------|
| **CX21** | 2 | 4 GB | 40 GB SSD | 20 TB | ~€5.39 |
| **CX31** | 2 | 8 GB | 80 GB SSD | 20 TB | ~€8.98 |
| **CX41** | 4 | 16 GB | 160 GB SSD | 20 TB | ~€17.98 |

### Recommendation:
- **Small/Medium traffic**: CX21 (4GB RAM)
- **Medium traffic + room to grow**: CX31 (8GB RAM) - **Recommended**
- **High traffic**: CX41 (16GB RAM)

---

## Step-by-Step Setup

### 1. Generate SSH Key (if you don't have one)

```bash
# Generate a new SSH key
ssh-keygen -t ed25519 -C "your-email@example.com"

# View your public key (copy this to Hetzner)
cat ~/.ssh/id_ed25519.pub
```

### 2. Create Server in Hetzner Console

1. Go to https://console.hetzner.cloud
2. Select your project (EG Construction)
3. Click **"Add Server"**
4. Configure:
   - **Type**: Regular Performance → CX21 or CX31
   - **Location**: Helsinki (or preferred)
   - **Image**: Ubuntu 24.04
   - **Networking**: ✅ Public IPv4, ✅ Public IPv6
   - **SSH Key**: Click "Add SSH key" → Paste your public key
   - **Backups**: ✅ Enable
   - **Name**: `eg-construction-prod`
5. Click **"Create & Buy now"**

### 3. Note Your Server Details

After creation, note down:
- **IP Address**: `xxx.xxx.xxx.xxx`
- **IPv6 Address**: `xxxx:xxxx:xxxx::1`

---

## Post-Creation Server Setup

### Connect to Server

```bash
ssh root@YOUR_SERVER_IP
```

### Initial Server Setup

```bash
# Update system
apt update && apt upgrade -y

# Set timezone
timedatectl set-timezone Africa/Harare

# Create non-root user
adduser egadmin
usermod -aG sudo egadmin

# Copy SSH key to new user
rsync --archive --chown=egadmin:egadmin ~/.ssh /home/egadmin
```

### Install Required Software

```bash
# Install Nginx
apt install nginx -y

# Install PHP 8.3 and extensions
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-xml php8.3-curl php8.3-gd php8.3-mbstring php8.3-zip php8.3-bcmath php8.3-redis -y

# Install MySQL
apt install mysql-server -y
mysql_secure_installation

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install nodejs -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install Redis (for queues/cache)
apt install redis-server -y

# Install Supervisor (for queue workers)
apt install supervisor -y

# Install Certbot (for SSL)
apt install certbot python3-certbot-nginx -y
```

### Configure Firewall

```bash
# Enable UFW
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw enable
```

---

## Nginx Configuration

Create `/etc/nginx/sites-available/eg-construction`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/eg-construction/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:

```bash
ln -s /etc/nginx/sites-available/eg-construction /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
```

---

## SSL Certificate (Let's Encrypt)

```bash
certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

---

## Deploy Application

```bash
# Create web directory
mkdir -p /var/www/eg-construction
chown -R egadmin:www-data /var/www/eg-construction

# Clone your repository (as egadmin user)
su - egadmin
cd /var/www/eg-construction
git clone YOUR_REPO_URL .

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Set permissions
sudo chown -R egadmin:www-data /var/www/eg-construction
sudo chmod -R 775 /var/www/eg-construction/storage
sudo chmod -R 775 /var/www/eg-construction/bootstrap/cache

# Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env with production settings

# Run migrations
php artisan migrate --force
php artisan db:seed --force

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Supervisor Configuration for Queue Workers

Create `/etc/supervisor/conf.d/eg-construction-worker.conf`:

```ini
[program:eg-construction-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/eg-construction/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=egadmin
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/eg-construction/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start eg-construction-worker:*
```

---

## Cron Job for Scheduler

```bash
# Edit crontab
crontab -e

# Add this line:
* * * * * cd /var/www/eg-construction && php artisan schedule:run >> /dev/null 2>&1
```

---

## Security Checklist

- [ ] SSH key authentication only (disable password auth)
- [ ] UFW firewall enabled
- [ ] Fail2ban installed
- [ ] Regular backups enabled
- [ ] SSL certificate installed
- [ ] APP_DEBUG=false in production
- [ ] APP_ENV=production

---

## Estimated Monthly Costs

| Item | Cost |
|------|------|
| CX31 Server | €8.98 |
| Backups (20%) | €1.80 |
| IPv4 Address | €0.00 (included) |
| **Total** | **~€10.78/mo** |

---

## Useful Commands

```bash
# Check server status
systemctl status nginx
systemctl status php8.3-fpm
systemctl status mysql
systemctl status supervisor

# View Laravel logs
tail -f /var/www/eg-construction/storage/logs/laravel.log

# Restart services
systemctl restart nginx
systemctl restart php8.3-fpm

# Clear Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```
