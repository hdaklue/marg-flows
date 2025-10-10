# MARG Ecosystem Infrastructure Guide

## Overview

This document outlines the infrastructure architecture for the MARG ecosystem, from initial deployment through scaling to support thousands of users. The architecture is designed to **start simple and scale horizontally** without requiring major refactoring.

---

## Architecture Philosophy

> **Start right, scale horizontally, never refactor.**

- ✅ Separate concerns from day one (HTTP, DB, Cache, Workers)
- ✅ Scale by adding servers, not changing architecture
- ✅ Maintain shared session across all apps
- ✅ Cost-effective at every stage

---

## Phase 1: Development (Current)

**Single Server - Everything on localhost**

```
┌─────────────────────────────────────────────┐
│         Local Development Machine           │
│                                             │
│  ┌──────────┐  ┌──────────┐               │
│  │flow.marg │  │links.marg│  ← Apps       │
│  └─────┬────┘  └─────┬────┘               │
│        └──────┬───────┘                    │
│               ▼                             │
│      Redis (localhost:6379)                │
│      MySQL (localhost:3306)                │
│      Queue Workers (artisan queue:work)    │
└─────────────────────────────────────────────┘
```

**Resources:**
- Cost: $0
- Users: Development only
- Perfect for: Local development, testing

**Configuration:**
```env
REDIS_HOST=127.0.0.1
DB_HOST=127.0.0.1
```

---

## Phase 2: Production Ready (0-500 Active Users)

**4-Server Architecture** - Recommended Starting Point

```
                    Internet
                        │
                ┌───────▼────────┐
                │ Load Balancer  │
                │  / Nginx       │
                │   ($5-10/mo)   │
                └───────┬────────┘
                        │
        ┌───────────────┼───────────────┐
        ▼               ▼               ▼
   ┌─────────┐    ┌─────────┐    ┌──────────┐
   │flow.marg│    │links.marg│   │future.marg│
   └────┬────┘    └────┬────┘    └────┬─────┘
        │              │              │
        └──────────────┴──────────────┘
                       │
        ┌──────────────┴──────────────┐
        ▼              ▼               ▼
   ┌─────────┐   ┌──────────┐   ┌──────────┐
   │  Redis  │   │  MySQL   │   │  Queue   │
   │  Server │   │  Server  │   │  Worker  │
   │ $10/mo  │   │ $15/mo   │   │  $5/mo   │
   └─────────┘   └──────────┘   └──────────┘
```

### Server Specifications

#### 1. HTTP Server ($5-10/month)
**DigitalOcean Droplet: 1GB RAM, 1 vCPU, 25GB SSD**

**Runs:**
- Nginx (web server)
- PHP 8.3-FPM
- All Laravel applications

**Capacity:**
- 1,000+ concurrent requests/second
- 100+ PHP-FPM workers

**Setup:**
```bash
# Install required packages
apt update && apt upgrade -y
apt install -y nginx php8.3-fpm composer git

# Clone applications
cd /var/www
git clone https://github.com/yourorg/flow.marg.git
git clone https://github.com/yourorg/links.marg.git

# Install dependencies
cd flow.marg && composer install --optimize-autoloader --no-dev
cd ../links.marg && composer install --optimize-autoloader --no-dev
```

**Nginx Configuration:**
```nginx
# /etc/nginx/sites-available/marg-ecosystem
server {
    listen 80;
    listen [::]:80;
    server_name *.marg.test flow.marg.test;
    root /var/www/flow.marg/public;

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
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Similar config for links.marg.test
server {
    listen 80;
    server_name links.marg.test;
    root /var/www/links.marg/public;
    # ... same configuration as above
}
```

---

#### 2. Redis Server ($10/month)
**DigitalOcean Droplet: 1GB RAM, 1 vCPU**

**Stores:**
- User sessions (`marg_database_laravel_session:*`)
- Application cache (`marg_cache_*`)
- Queue jobs (`marg_database_queues:*`)
- RBAC cache

**Capacity:**
- 10,000+ operations/second
- 512MB memory for cache/sessions

**Setup:**
```bash
# Install Redis
apt update
apt install -y redis-server

# Configure Redis
nano /etc/redis/redis.conf
```

**Redis Configuration:**
```conf
# /etc/redis/redis.conf
bind 0.0.0.0
protected-mode yes
requirepass your_secure_redis_password_here
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Append-only file
appendonly yes
appendfilename "appendonly.aof"
```

**Security:**
```bash
# Firewall - only allow HTTP server
ufw allow from <http-server-ip> to any port 6379
ufw enable
```

---

#### 3. MySQL Server ($15-20/month)
**DigitalOcean Droplet: 2GB RAM, 1 vCPU, 50GB SSD**

**Databases:**
- `klueportal` - flow.marg main database
- `links_db` - links.marg database
- `marg-rbac` - Shared RBAC database
- `klue_portal_business` - Business/feedback data

**Capacity:**
- 500+ queries/second
- 10,000+ active connections

**Setup:**
```bash
# Install MySQL
apt update
apt install -y mysql-server

# Secure installation
mysql_secure_installation
```

**MySQL Configuration:**
```sql
# Create databases
CREATE DATABASE klueportal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE links_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE `marg-rbac` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE klue_portal_business CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user
CREATE USER 'marg_user'@'%' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON klueportal.* TO 'marg_user'@'%';
GRANT ALL PRIVILEGES ON links_db.* TO 'marg_user'@'%';
GRANT ALL PRIVILEGES ON `marg-rbac`.* TO 'marg_user'@'%';
GRANT ALL PRIVILEGES ON klue_portal_business.* TO 'marg_user'@'%';
FLUSH PRIVILEGES;
```

**Optimization (`/etc/mysql/mysql.conf.d/mysqld.cnf`):**
```conf
[mysqld]
max_connections = 200
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
query_cache_size = 64M
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
```

---

#### 4. Queue Worker Server ($5/month)
**DigitalOcean Droplet: 512MB RAM, 1 vCPU**

**Processes:**
- Email sending
- Image processing (avatars, documents)
- Report generation
- Background tasks
- RBAC cache warming

**Capacity:**
- 100+ jobs/minute
- 3-5 concurrent workers

**Setup:**
```bash
# Install PHP and dependencies
apt update
apt install -y php8.3-cli php8.3-redis php8.3-mysql supervisor

# Clone application code (or mount shared storage)
cd /var/www
git clone https://github.com/yourorg/flow.marg.git
cd flow.marg && composer install --optimize-autoloader --no-dev
```

**Supervisor Configuration:**
```conf
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/flow.marg/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/www/flow.marg/storage/logs/worker.log
stopwaitsecs=3600
```

**Manage Workers:**
```bash
# Start workers
supervisorctl reread
supervisorctl update
supervisorctl start laravel-worker:*

# Monitor workers
supervisorctl status
```

---

### Shared Configuration (.env.shared)

```env
# App-specific (override in each app)
# APP_NAME=
# APP_URL=
# DB_DATABASE=

# Shared Authentication
APP_KEY=base64:hnAiCHfq9WAEXzFxP0/q/SteYkgSOdZBEmgWdkOhm+Y=

# Session (CRITICAL - must be identical)
SESSION_DRIVER=redis
SESSION_LIFETIME=10080
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=.marg.test
SESSION_COOKIE=marg_session

# Redis Server
REDIS_HOST=redis.marg.internal  # or IP: 192.168.1.50
REDIS_PORT=6379
REDIS_PASSWORD=your_secure_redis_password
REDIS_DB=0
REDIS_PREFIX=marg_database_

# Cache
CACHE_STORE=redis
CACHE_PREFIX=marg

# MySQL Server
DB_HOST=mysql.marg.internal  # or IP: 192.168.1.51
DB_PORT=3306
DB_USERNAME=marg_user
DB_PASSWORD=secure_password_here

# RBAC Database (shared)
RBAC_DB_CONNECTION=rbac
RBAC_DB_DRIVER=mysql
RBAC_DB_HOST=mysql.marg.internal
RBAC_DB_PORT=3306
RBAC_DB_DATABASE=marg-rbac
RBAC_DB_USERNAME=marg_user
RBAC_DB_PASSWORD=secure_password_here
RBAC_SESSION_DRIVER=redis
RBAC_SESSION_CONNECTION=default
RBAC_CACHE_ENABLED=true
RBAC_CACHE_TTL=3600

# Queue
QUEUE_CONNECTION=redis
```

---

### Cost Breakdown - Phase 2

| Server | Specs | Cost/Month |
|--------|-------|------------|
| HTTP Server | 1GB RAM, 1 vCPU | $5-10 |
| Redis Server | 1GB RAM, 1 vCPU | $10 |
| MySQL Server | 2GB RAM, 1 vCPU | $15-20 |
| Queue Worker | 512MB RAM, 1 vCPU | $5 |
| **Total** | | **$35-45** |

**Handles:** 500-5,000 active users

---

## Phase 3: Horizontal Scaling (500-5,000 Users)

**Add more HTTP servers and workers as needed**

```
                    Internet
                        │
                ┌───────▼────────┐
                │ Load Balancer  │
                │   (HAProxy)    │
                │   ($10/mo)     │
                └───────┬────────┘
                        │
        ┌───────────────┼───────────────┐
        ▼               ▼               ▼
   ┌──────────┐   ┌──────────┐   ┌──────────┐
   │  HTTP 1  │   │  HTTP 2  │   │  HTTP 3  │  ← Scale horizontally
   │ $10/mo   │   │ $10/mo   │   │ $10/mo   │
   └────┬─────┘   └────┬─────┘   └────┬─────┘
        │              │              │
        └──────────────┴──────────────┘
                       │
        ┌──────────────┴──────────────┐
        ▼              ▼               ▼
   ┌─────────┐   ┌──────────┐   ┌──────────┐
   │  Redis  │   │  MySQL   │   │ Workers  │
   │ $20/mo  │   │ $30/mo   │   │ (3x $5)  │
   │(Upgraded│   │(Upgraded)│   │  $15/mo  │
   └─────────┘   └──────────┘   └──────────┘
```

### Load Balancer Setup

**HAProxy Configuration:**
```conf
# /etc/haproxy/haproxy.cfg
frontend http_front
    bind *:80
    mode http
    default_backend http_back

backend http_back
    mode http
    balance roundrobin
    option httpchk GET /health
    server http1 192.168.1.10:80 check
    server http2 192.168.1.11:80 check
    server http3 192.168.1.12:80 check
```

### Cost Breakdown - Phase 3

| Component | Cost/Month |
|-----------|------------|
| Load Balancer | $10 |
| HTTP Servers (3x) | $30 |
| Redis Server (upgraded) | $20 |
| MySQL Server (upgraded) | $30 |
| Queue Workers (3x) | $15 |
| **Total** | **$105** |

**Handles:** 5,000-10,000 active users

---

## Phase 4: High Availability (5,000-50,000 Users)

**Add redundancy and read replicas**

```
                    Internet
                        │
                ┌───────▼────────┐
                │ Load Balancer  │
                │   (Redundant)  │
                │   ($20/mo)     │
                └───────┬────────┘
                        │
                 HTTP Servers (5x)
                    $50/mo
                        │
        ┌───────────────┴───────────────┐
        ▼               ▼               ▼
   ┌─────────┐   ┌──────────┐   ┌──────────┐
   │ Redis   │   │  MySQL   │   │ Workers  │
   │Sentinel │   │ Primary  │   │  (5x)    │
   │  (3x)   │   │ $80/mo   │   │ $25/mo   │
   │ $60/mo  │   │    +     │   │          │
   │         │   │ Replicas │   │          │
   │         │   │ (2x)     │   │          │
   │         │   │ $80/mo   │   │          │
   └─────────┘   └──────────┘   └──────────┘
```

### Redis Sentinel (High Availability)

**3 Sentinel Nodes Monitor Redis Master:**
```bash
# sentinel.conf
sentinel monitor marg-redis redis-master 6379 2
sentinel down-after-milliseconds marg-redis 5000
sentinel failover-timeout marg-redis 10000
sentinel parallel-syncs marg-redis 1
```

**Application Configuration:**
```env
REDIS_SENTINELS=sentinel1:26379,sentinel2:26379,sentinel3:26379
REDIS_SENTINEL_SERVICE=marg-redis
```

### MySQL Replication

**Primary-Replica Setup:**
```sql
-- On Primary
CREATE USER 'replicator'@'%' IDENTIFIED BY 'secure_password';
GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'%';

-- On Replicas
CHANGE MASTER TO
  MASTER_HOST='mysql-primary',
  MASTER_USER='replicator',
  MASTER_PASSWORD='secure_password',
  MASTER_LOG_FILE='mysql-bin.000001',
  MASTER_LOG_POS=107;
START SLAVE;
```

**Laravel Configuration:**
```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => ['mysql-replica1', 'mysql-replica2'],
    ],
    'write' => [
        'host' => ['mysql-primary'],
    ],
    // ... other config
],
```

### Cost Breakdown - Phase 4

| Component | Cost/Month |
|-----------|------------|
| Load Balancer (HA) | $20 |
| HTTP Servers (5x) | $50 |
| Redis Sentinel (3x) | $60 |
| MySQL Primary | $80 |
| MySQL Replicas (2x) | $80 |
| Queue Workers (5x) | $25 |
| **Total** | **$315** |

**Handles:** 50,000+ active users

---

## Capacity Planning

### Phase 2 Performance (4-Server Setup)

**Load Capacity:**
- 500 active users × 10 requests/min = 5,000 requests/min
- = 83 requests/second

**Server Capacity:**
- Nginx: 1,000+ req/sec ✅ (12x headroom)
- PHP-FPM: 200+ req/sec ✅ (2.5x headroom)
- Redis: 10,000+ ops/sec ✅ (120x headroom)
- MySQL: 500+ queries/sec ✅ (6x headroom)

**Bottleneck:** PHP-FPM workers (scale horizontally when needed)

---

## Deployment Workflow

### Automated Deployment Script

```bash
#!/bin/bash
# deploy.sh - Run on HTTP servers

set -e

APP_PATH="/var/www/flow.marg"
BRANCH="main"

echo "🚀 Starting deployment..."

# Navigate to app
cd $APP_PATH

# Enable maintenance mode
php artisan down --message="Deploying updates..." --retry=60

# Pull latest code
git fetch origin
git reset --hard origin/$BRANCH

# Install dependencies
composer install --optimize-autoloader --no-dev --no-interaction

# Run migrations
php artisan migrate --force --no-interaction

# Clear and rebuild caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize
php artisan optimize

# Reload PHP-FPM
sudo systemctl reload php8.3-fpm

# Restart queue workers (if on same server)
if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart laravel-worker:*
fi

# Disable maintenance mode
php artisan up

echo "✅ Deployment complete!"
```

### Zero-Downtime Deployment (Phase 3+)

```bash
# Deploy to servers one at a time
for server in http1 http2 http3; do
    echo "Deploying to $server..."

    # Remove from load balancer
    haproxy-disable-server $server

    # Deploy
    ssh $server 'bash /var/www/deploy.sh'

    # Add back to load balancer
    haproxy-enable-server $server

    echo "$server deployed successfully"
    sleep 5
done
```

---

## Monitoring & Alerts

### Server Monitoring (All Phases)

**Install on each server:**
```bash
# Install monitoring agent
curl -Ls https://insight.uptimerobot.com/install.sh | bash

# Or use custom monitoring
apt install -y prometheus-node-exporter
```

**Monitor:**
- CPU usage (alert > 80%)
- Memory usage (alert > 90%)
- Disk space (alert > 85%)
- Network traffic
- Service uptime

### Application Monitoring

**Laravel Telescope (Development):**
```env
TELESCOPE_ENABLED=true
```

**Laravel Horizon (Queue Monitoring):**
```bash
php artisan horizon:install
```

**Custom Health Check Endpoint:**
```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'redis' => Cache::getRedis()->ping() ? 'connected' : 'disconnected',
        'queue' => Queue::size() < 1000 ? 'healthy' : 'backlog',
    ]);
});
```

### Backup Strategy

**MySQL Backups:**
```bash
#!/bin/bash
# /usr/local/bin/mysql-backup.sh

BACKUP_DIR="/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)

mysqldump --all-databases > $BACKUP_DIR/all_databases_$DATE.sql
gzip $BACKUP_DIR/all_databases_$DATE.sql

# Keep only last 7 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete
```

**Redis Backups:**
```bash
# Redis automatically saves to disk (RDB + AOF)
# Copy backup files
cp /var/lib/redis/dump.rdb /backups/redis/dump_$(date +%Y%m%d).rdb
cp /var/lib/redis/appendonly.aof /backups/redis/aof_$(date +%Y%m%d).aof
```

---

## Security Best Practices

### Firewall Rules (UFW)

**HTTP Server:**
```bash
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
ufw enable
```

**Redis Server:**
```bash
ufw allow from <http-server-ip> to any port 6379
ufw allow 22/tcp
ufw enable
```

**MySQL Server:**
```bash
ufw allow from <http-server-ip> to any port 3306
ufw allow from <worker-server-ip> to any port 3306
ufw allow 22/tcp
ufw enable
```

### SSL Certificates

```bash
# Install Certbot
apt install -y certbot python3-certbot-nginx

# Generate certificates
certbot --nginx -d flow.marg.test -d links.marg.test

# Auto-renewal
certbot renew --dry-run
```

### Environment Security

```bash
# Restrict .env permissions
chmod 600 .env
chown www-data:www-data .env

# Secure storage directory
chmod -R 755 storage
chown -R www-data:www-data storage
```

---

## Migration Checklist

### From Development to Phase 2 Production

- [ ] Provision 4 servers (HTTP, Redis, MySQL, Worker)
- [ ] Configure firewall rules on all servers
- [ ] Install and configure services (Nginx, Redis, MySQL, Supervisor)
- [ ] Update DNS records for domain
- [ ] Update `.env` files with production server IPs
- [ ] Deploy application code to HTTP server
- [ ] Run migrations on MySQL server
- [ ] Configure SSL certificates
- [ ] Set up supervisor for queue workers
- [ ] Test session sharing between apps
- [ ] Configure backups (MySQL, Redis)
- [ ] Set up monitoring and alerts
- [ ] Test health check endpoints
- [ ] Perform load testing
- [ ] Document server access credentials (password manager)

---

## Troubleshooting

### Session Not Shared Between Apps

**Check:**
```bash
# 1. Verify Redis prefix
redis-cli -h redis.marg.internal
> KEYS marg_database_*

# 2. Check session cookie
# In browser DevTools: Application → Cookies
# Should see: marg_session with domain .marg.test

# 3. Verify .env matches
grep -E "APP_KEY|REDIS_PREFIX|SESSION" .env
```

### Queue Jobs Stuck

**Check:**
```bash
# Worker status
supervisorctl status laravel-worker:*

# Queue size
redis-cli -h redis.marg.internal
> LLEN marg_database_queues:default

# Restart workers
supervisorctl restart laravel-worker:*
```

### High Database Load

**Check slow queries:**
```sql
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;
```

**Add indexes:**
```php
Schema::table('tasks', function (Blueprint $table) {
    $table->index('user_id');
    $table->index(['status', 'created_at']);
});
```

---

## Conclusion

This infrastructure is designed to:
- ✅ Start simple and cost-effective ($35/month)
- ✅ Scale horizontally without refactoring
- ✅ Maintain shared sessions across all apps
- ✅ Support growth from 100 to 50,000+ users
- ✅ Provide clear upgrade paths at each phase

**Remember:** Don't over-engineer early. Start with Phase 2, monitor performance, and scale when metrics indicate the need.

---

**Last Updated:** 2025-10-06
**Maintained By:** Development Team