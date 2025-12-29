# Production Deployment Guide

## Pre-Deployment Checklist

### 1. Environment Configuration
- [ ] Copy `.env.example` to `.env`
- [ ] Set `APP_ENV=production`
- [ ] Configure database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)
- [ ] Update `APP_BASE_URL` to production domain (must be HTTPS)
- [ ] Configure SMTP settings for email delivery
- [ ] Verify `COOKIE_SECURE=true` (automatic in production)

### 2. Database Setup
```bash
# Import the database schema
mysql -u your_user -p your_database < apps/auth/sql/migration.sql

# Verify tables were created
mysql -u your_user -p your_database -e "SHOW TABLES;"
```

### 3. File Permissions
```bash
# Set correct permissions
chmod 755 apps/auth/logs
chmod 644 apps/auth/config.php
chmod 600 apps/auth/.env
chmod 755 apps/auth/errors

# Ensure web server can write to logs
chown www-data:www-data apps/auth/logs  # or appropriate user
```

### 4. Dependencies
```bash
# Install Composer dependencies
cd apps/auth
composer install --no-dev --optimize-autoloader
```

### 5. Security Verification
- [ ] `.env` file is NOT in version control (check .gitignore)
- [ ] HTTPS is enforced on production domain
- [ ] Database passwords are strong and unique
- [ ] SMTP credentials are secure
- [ ] Admin accounts use strong passwords (8+ chars, uppercase, lowercase, numbers, special chars)

### 6. Testing on Staging
Before deploying to production, test on a staging environment:
- [ ] User registration/login works
- [ ] Password reset emails are delivered
- [ ] Admin panel is accessible
- [ ] Session management works correctly
- [ ] Error pages display properly (404, 403, 500)
- [ ] Security headers are present (check browser dev tools)

## Deployment Steps

### Step 1: Upload Files
```bash
# Using rsync (recommended)
rsync -avz --exclude='.git' --exclude='node_modules' --exclude='.env' \
  /path/to/local/loginveerles/ user@server:/path/to/production/

# Or using FTP/SFTP
# Upload all files except .git, .env, and logs/
```

### Step 2: Configure Production Environment
```bash
# SSH into production server
ssh user@server

# Navigate to app directory
cd /path/to/production/loginveerles/apps/auth

# Create production .env from template
cp .env.production .env
nano .env  # Edit with production values

# Set correct ownership
sudo chown -R www-data:www-data /path/to/production/loginveerles
```

### Step 3: Web Server Configuration

#### Apache (.htaccess)
Create `/loginveerles/.htaccess`:
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Prevent access to sensitive files
<FilesMatch "^\.env">
    Require all denied
</FilesMatch>

<FilesMatch "^(composer\.(json|lock)|\.git)">
    Require all denied
</FilesMatch>
```

#### Nginx
Add to your server block:
```nginx
# Force HTTPS
if ($scheme != "https") {
    return 301 https://$server_name$request_uri;
}

# Prevent access to sensitive files
location ~ /\.env {
    deny all;
}

location ~ /(composer\.(json|lock)|\.git) {
    deny all;
}

# PHP handling
location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
}
```

### Step 4: Create First Admin User
```bash
# Access the database
mysql -u your_user -p your_database

# Create admin user
INSERT INTO users (email, password_hash, first_name, last_name, role, is_active, created_at)
VALUES ('admin@yourdomain.com', NULL, 'Admin', 'User', 'Admin', 1, NOW());

# Exit MySQL
exit;
```

Then visit: `https://yourdomain.com/loginveerles/apps/auth/password_reset_request.php`
Enter the admin email to set up the initial password.

### Step 5: Verify Production Deployment
- [ ] Visit login page - should load without errors
- [ ] Check browser console - no JavaScript errors
- [ ] Test login with admin account
- [ ] Verify security headers (use https://securityheaders.com)
- [ ] Check SSL certificate (use https://www.ssllabs.com/ssltest/)
- [ ] Test password reset flow
- [ ] Verify logs are being written (`apps/auth/logs/`)

## Post-Deployment Monitoring

### 1. Log Monitoring
```bash
# Watch real-time logs
tail -f apps/auth/logs/$(date +%Y-%m-%d).log

# Check for errors
grep "ERROR" apps/auth/logs/*.log

# Monitor security events
grep "SECURITY" apps/auth/logs/*.log
```

### 2. Database Backups
Set up automated daily backups:
```bash
# Add to crontab (crontab -e)
0 2 * * * /usr/bin/mysqldump -u backup_user -p'backup_pass' your_database | gzip > /backups/db_$(date +\%Y\%m\%d).sql.gz
```

### 3. Log Rotation
Create `/etc/logrotate.d/loginveerles`:
```
/path/to/production/loginveerles/apps/auth/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    missingok
    create 0644 www-data www-data
}
```

## Troubleshooting

### Issue: White screen / 500 error
**Solution:**
1. Check PHP error log: `tail -f /var/log/php8.1-fpm.log`
2. Verify .env file exists and is readable
3. Check database connection settings
4. Ensure all directories have correct permissions

### Issue: Sessions not persisting
**Solution:**
1. Verify `COOKIE_SECURE=true` and site is HTTPS
2. Check cookie domain matches your domain
3. Ensure session directory is writable: `ls -la /var/lib/php/sessions`

### Issue: Emails not sending
**Solution:**
1. Check SMTP credentials in .env
2. Verify firewall allows outbound port 465/587
3. Check logs: `grep "SMTP" apps/auth/logs/*.log`
4. Test SMTP connection manually: `telnet mail.yourdomain.com 465`

### Issue: Admin panel shows 403
**Solution:**
1. Verify user role is exactly "Admin" (case-sensitive)
2. Check user `is_active` = 1 in database
3. Clear browser cache and cookies
4. Check security logs for access attempts

## Security Maintenance

### Regular Tasks
- [ ] Review security logs weekly
- [ ] Update dependencies monthly: `composer update`
- [ ] Rotate SMTP passwords quarterly
- [ ] Review admin user list monthly
- [ ] Test backup restoration quarterly
- [ ] Update PHP to latest stable version

### Incident Response
If you detect suspicious activity:
1. Check security logs: `grep "SECURITY" apps/auth/logs/*.log`
2. Disable compromised accounts via admin panel
3. Force password reset for affected users
4. Review and revoke active sessions if needed
5. Update credentials (database, SMTP, etc.)

## Support & Updates

### Updating the Application
```bash
# Backup database first
mysqldump -u user -p database > backup_before_update.sql

# Pull latest changes
git pull origin main

# Run any new migrations (if provided)
mysql -u user -p database < apps/auth/sql/new_migration.sql

# Update dependencies
cd apps/auth
composer install --no-dev --optimize-autoloader

# Clear cache if applicable
php -r "opcache_reset();"
```

## Performance Optimization

### Enable OPcache
Add to `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### Database Indexing
Verify indexes are present:
```sql
SHOW INDEX FROM users;
SHOW INDEX FROM sessions;
SHOW INDEX FROM login_attempts;
```

### CDN for Static Assets
Consider serving assets (CSS, images, fonts) via CDN for better performance.

---

**Last Updated:** December 29, 2025  
**For Support:** Contact your system administrator
