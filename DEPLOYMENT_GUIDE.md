# Deployment Guide - Medical Appointment Booking System

This guide provides step-by-step instructions for deploying the Medical Appointment Booking System to a production environment.

## 🚀 Pre-Deployment Checklist

### System Requirements
- [ ] PHP 7.4+ installed
- [ ] MySQL 5.7+ or MariaDB 10.3+ installed
- [ ] Web server (Apache/Nginx) configured
- [ ] SSL certificate obtained
- [ ] Domain name configured
- [ ] Email server configured (for notifications)

### Security Preparations
- [ ] Change all default passwords
- [ ] Generate new CSRF tokens
- [ ] Configure secure session settings
- [ ] Set up proper file permissions
- [ ] Configure security headers
- [ ] Set up firewall rules

## 🔧 Production Configuration

### 1. Database Configuration

#### Create Production Database
```sql
CREATE DATABASE medical_appointment_system_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'medical_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT SELECT, INSERT, UPDATE, DELETE ON medical_appointment_system_prod.* TO 'medical_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Update Database Configuration
Edit `config/database.php`:
```php
<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'medical_appointment_system_prod';
    private $username = 'medical_user';
    private $password = 'your_strong_password';
    // ... rest of the configuration
}
```

### 2. Environment Variables (Recommended)

Create `.env` file in the root directory:
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=medical_appointment_system_prod
DB_USER=medical_user
DB_PASS=your_strong_password

# Security
CSRF_SECRET=your_csrf_secret_key
SESSION_SECRET=your_session_secret_key

# Email Configuration
SMTP_HOST=smtp.your-domain.com
SMTP_PORT=587
SMTP_USER=noreply@your-domain.com
SMTP_PASS=your_email_password

# Application Settings
APP_URL=https://your-domain.com
APP_ENV=production
DEBUG_MODE=false
```

Update `config/database.php` to use environment variables:
```php
<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    
    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'medical_appointment_system';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
    }
    // ... rest of the class
}
```

### 3. Web Server Configuration

#### Apache Configuration
Create/update `.htaccess`:
```apache
# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; font-src 'self' cdnjs.cloudflare.com; img-src 'self' data:;"

# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Protect sensitive files
<Files "*.env">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Disable server signature
ServerSignature Off

# Prevent access to PHP files in uploads directory
<Directory "uploads">
    <Files "*.php">
        Order allow,deny
        Deny from all
    </Files>
</Directory>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Set cache headers
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/ico "access plus 1 year"
    ExpiresByType image/icon "access plus 1 year"
    ExpiresByType text/plain "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/html "access plus 1 hour"
</IfModule>
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    
    root /var/www/medical-appointment-system;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # Security Headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; font-src 'self' cdnjs.cloudflare.com; img-src 'self' data:;";
    
    # Protect sensitive files
    location ~ /\.env {
        deny all;
    }
    
    location ~ /\.ht {
        deny all;
    }
    
    location ~ \.log$ {
        deny all;
    }
    
    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_hide_header X-Powered-By;
    }
    
    # Static files caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Prevent PHP execution in uploads
    location ^~ /uploads/ {
        location ~ \.php$ {
            deny all;
        }
    }
    
    # Main location block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

### 4. File Permissions

Set proper file permissions:
```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/medical-appointment-system

# Set directory permissions
find /var/www/medical-appointment-system -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/medical-appointment-system -type f -exec chmod 644 {} \;

# Set executable permissions for specific files
chmod +x /var/www/medical-appointment-system/init.php

# Set writable permissions for logs and uploads
chmod 755 /var/www/medical-appointment-system/logs
chmod 755 /var/www/medical-appointment-system/uploads

# Protect sensitive files
chmod 600 /var/www/medical-appointment-system/.env
chmod 600 /var/www/medical-appointment-system/config/database.php
```

### 5. PHP Configuration

Update `php.ini` for production:
```ini
; Error handling
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"

; File uploads
file_uploads = On
upload_max_filesize = 10M
max_file_uploads = 5

; Memory and execution
memory_limit = 256M
max_execution_time = 30
max_input_time = 60

; Post and GET limits
post_max_size = 20M
max_input_vars = 3000
```

## 🔐 Security Hardening

### 1. Database Security
```sql
-- Remove test databases
DROP DATABASE IF EXISTS test;

-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Remove remote root access
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- Reload privileges
FLUSH PRIVILEGES;
```

### 2. Firewall Configuration
```bash
# UFW (Ubuntu)
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable

# Or for Apache
sudo ufw allow 'Apache Full'
```

### 3. Fail2Ban Configuration
Create `/etc/fail2ban/jail.local`:
```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled = true

[php-url-fopen]
enabled = true
```

## 📧 Email Configuration

### SMTP Configuration
Update email settings in your configuration:
```php
// Email configuration
define('SMTP_HOST', 'smtp.your-domain.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@your-domain.com');
define('SMTP_PASSWORD', 'your_email_password');
define('SMTP_ENCRYPTION', 'tls');
```

## 📊 Monitoring and Logging

### 1. Log Rotation
Create `/etc/logrotate.d/medical-appointment-system`:
```
/var/www/medical-appointment-system/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### 2. Health Check Script
Create `health-check.php`:
```php
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'checks' => []
];

// Database check
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $health['checks']['database'] = $db ? 'ok' : 'failed';
} catch (Exception $e) {
    $health['checks']['database'] = 'failed';
    $health['status'] = 'error';
}

// Disk space check
$freeBytes = disk_free_space('.');
$totalBytes = disk_total_space('.');
$usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;
$health['checks']['disk_space'] = $usedPercent < 90 ? 'ok' : 'warning';

echo json_encode($health, JSON_PRETTY_PRINT);
?>
```

## 🚀 Deployment Steps

### 1. Prepare the Server
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install nginx php7.4-fpm php7.4-mysql php7.4-mbstring php7.4-xml php7.4-curl mysql-server -y

# Secure MySQL installation
sudo mysql_secure_installation
```

### 2. Deploy the Application
```bash
# Clone repository
git clone https://github.com/yourusername/medical-appointment-system.git /var/www/medical-appointment-system

# Set permissions
sudo chown -R www-data:www-data /var/www/medical-appointment-system

# Copy environment file
cp .env.example .env
# Edit .env with production values

# Initialize database
php init.php
```

### 3. Configure SSL
```bash
# Using Let's Encrypt
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

### 4. Test the Deployment
- [ ] Access the application via HTTPS
- [ ] Test user registration and login
- [ ] Verify appointment booking functionality
- [ ] Check admin panel access
- [ ] Test email notifications
- [ ] Verify security headers
- [ ] Run health check endpoint

## 🔄 Backup Strategy

### 1. Database Backup Script
Create `backup-db.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/medical-appointment-system"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="medical_appointment_system_prod"
DB_USER="medical_user"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR

mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql
gzip $BACKUP_DIR/db_backup_$DATE.sql

# Keep only last 30 days of backups
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +30 -delete
```

### 2. File Backup Script
Create `backup-files.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/medical-appointment-system"
SOURCE_DIR="/var/www/medical-appointment-system"
DATE=$(date +%Y%m%d_%H%M%S)

tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz -C $SOURCE_DIR uploads/ logs/ .env

# Keep only last 7 days of file backups
find $BACKUP_DIR -name "files_backup_*.tar.gz" -mtime +7 -delete
```

### 3. Automated Backups
Add to crontab:
```bash
# Daily database backup at 2 AM
0 2 * * * /var/www/medical-appointment-system/backup-db.sh

# Weekly file backup on Sundays at 3 AM
0 3 * * 0 /var/www/medical-appointment-system/backup-files.sh
```

## 🚨 Incident Response

### Emergency Contacts
- System Administrator: admin@your-domain.com
- Database Administrator: dba@your-domain.com
- Security Team: security@your-domain.com

### Recovery Procedures
1. **Database Recovery**:
   ```bash
   mysql -u medical_user -p medical_appointment_system_prod < backup_file.sql
   ```

2. **File Recovery**:
   ```bash
   tar -xzf files_backup_YYYYMMDD_HHMMSS.tar.gz -C /var/www/medical-appointment-system/
   ```

3. **Service Restart**:
   ```bash
   sudo systemctl restart nginx
   sudo systemctl restart php7.4-fpm
   sudo systemctl restart mysql
   ```

## 📈 Performance Optimization

### 1. Enable OPcache
In `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 2. Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_appointments_date_doctor ON appointments(appointment_date, doctor_id);
CREATE INDEX idx_appointments_patient_status ON appointments(patient_id, status);
CREATE INDEX idx_users_email_active ON users(email, is_active);
```

### 3. Enable Gzip Compression
Already included in the web server configurations above.

## ✅ Post-Deployment Checklist

- [ ] SSL certificate installed and working
- [ ] All default passwords changed
- [ ] Database backups configured
- [ ] File backups configured
- [ ] Monitoring configured
- [ ] Log rotation configured
- [ ] Firewall configured
- [ ] Email notifications working
- [ ] Health check endpoint responding
- [ ] Security headers verified
- [ ] Performance optimizations applied
- [ ] Documentation updated
- [ ] Team trained on new system

---

**Deployment completed successfully! 🎉**

For ongoing maintenance and support, refer to the main README.md file.