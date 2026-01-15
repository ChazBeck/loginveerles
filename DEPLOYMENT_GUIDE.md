# Production Deployment Guide - JWT Authentication Refactoring

## 🎯 Goal
Deploy the refactored JWT authentication system to tools.veerl.es

## ⚠️ IMPORTANT: Before You Start
- Current production system is running
- Have SSH access to production server
- Have database backup capability
- Know your production database credentials

---

## 📋 Step-by-Step Deployment

### Step 1: Backup Production Database
**On your production server (via SSH):**

```bash
# SSH into production server
ssh your-username@tools.veerl.es

# Navigate to a backup directory
cd ~
mkdir -p backups
cd backups

# Create database backup
mysqldump -u your_db_user -p your_db_name > backup_before_jwt_refactor_$(date +%Y%m%d_%H%M%S).sql

# Verify backup was created
ls -lh backup_before_jwt_refactor_*.sql
```

✅ **Checkpoint**: You should see a .sql file with today's date

---

### Step 2: Check Current Production Schema
**Still on production server:**

```bash
mysql -u your_db_user -p your_db_name
```

Then in MySQL:
```sql
-- Check sessions table structure
DESCRIBE sessions;

-- Check if jti and revoked_at columns exist
SHOW COLUMNS FROM sessions LIKE 'jti';
SHOW COLUMNS FROM sessions LIKE 'revoked_at';

-- Exit MySQL
exit;
```

✅ **Checkpoint**: Note whether jti and revoked_at columns already exist

---

### Step 3: Pull Latest Code from Git
**On production server:**

```bash
# Navigate to your web directory
cd /path/to/tools.veerl.es/web/root

# Check current branch
git branch

# Stash any local changes (if needed)
git stash

# Checkout main branch if not already there
git checkout main

# Pull latest changes
git pull origin main

# If you want to use the refactor branch instead:
# git checkout refactor/code-improvements
# git pull origin refactor/code-improvements
```

✅ **Checkpoint**: Should see "Already up to date" or list of files updated

---

### Step 4: Run Database Migration
**On production server:**

```bash
# Navigate to sql directory
cd /path/to/tools.veerl.es/web/root/sql

# View the migration SQL
cat update_sessions_for_jwt.sql

# Run the migration
mysql -u your_db_user -p your_db_name < update_sessions_for_jwt.sql
```

**Or run it step-by-step via MySQL:**
```bash
mysql -u your_db_user -p your_db_name
```

Then:
```sql
-- Add jti column
ALTER TABLE sessions 
    ADD COLUMN jti CHAR(64) NULL COMMENT 'JWT ID for tracking and revocation' AFTER user_id;

-- Add revoked_at column
ALTER TABLE sessions 
    ADD COLUMN revoked_at DATETIME DEFAULT NULL COMMENT 'Timestamp when JWT was revoked';

-- Create index on jti
ALTER TABLE sessions ADD UNIQUE KEY idx_jti (jti);

-- Make session_token nullable (for compatibility)
ALTER TABLE sessions MODIFY COLUMN session_token CHAR(64) NULL DEFAULT NULL;

-- Verify changes
DESCRIBE sessions;

-- Exit
exit;
```

✅ **Checkpoint**: DESCRIBE sessions should show jti and revoked_at columns

---

### Step 5: Update Production Configuration
**On production server:**

```bash
# Navigate to your web root
cd /path/to/tools.veerl.es/web/root

# Check if .env file exists
ls -la .env

# If .env doesn't exist, copy from sample
# cp .env.sample .env

# Edit .env file
nano .env
```

**Required .env settings:**
```env
# Database
DB_HOST=localhost
DB_NAME=your_production_db_name
DB_USER=your_production_db_user
DB_PASS=your_production_db_password

# JWT Settings
JWT_COOKIE_NAME=sso_token
JWT_COOKIE_DOMAIN=.veerl.es
JWT_LIFETIME_MINUTES=60

# Cookie Security (IMPORTANT for production)
COOKIE_SECURE=true
COOKIE_SAMESITE=Lax
COOKIE_PATH=/

# Environment
APP_ENV=production

# SMTP (for password reset emails)
SMTP_HOST=your_smtp_host
SMTP_PORT=587
SMTP_USER=your_smtp_user
SMTP_PASS=your_smtp_password
SMTP_FROM=noreply@veerl.es
```

**Save and exit** (Ctrl+O, Enter, Ctrl+X in nano)

✅ **Checkpoint**: .env file has correct JWT_COOKIE_DOMAIN=.veerl.es

---

### Step 6: Check File Permissions
**On production server:**

```bash
# Ensure web server can read files
chmod 644 .env
chmod 644 config.php
chmod 644 jwt_public.pem
chmod 600 jwt_private.pem  # Private key should be more restrictive

# Check JWT keys exist
ls -l jwt_*.pem
```

✅ **Checkpoint**: Both jwt_public.pem and jwt_private.pem exist

---

### Step 7: Clear Old Sessions (Optional but Recommended)
**On production server:**

```bash
mysql -u your_db_user -p your_db_name
```

```sql
-- See current sessions
SELECT COUNT(*) FROM sessions;

-- Delete old session-based sessions (optional)
-- DELETE FROM sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Or clear all sessions to force re-login
-- DELETE FROM sessions;

exit;
```

✅ **Checkpoint**: Decide if you want to force all users to re-login

---

### Step 8: Test on Production
**In your web browser:**

1. **Test Login (Admin)**
   - Go to https://tools.veerl.es/login.php
   - Log in with your admin credentials
   - Should redirect to admin panel
   - ✅ Verify you see user management page

2. **Test Logout**
   - Click "LOG OUT"
   - Should redirect to login page
   - ✅ Verify you're logged out

3. **Test Regular User** (if applicable)
   - Log in with testuser@example.com / testpass123
   - Should redirect to index.php (not admin panel)
   - Try accessing /admin/index.php directly
   - Should be redirected or see "Forbidden"
   - ✅ Verify regular users can't access admin panel

4. **Test Already Logged In**
   - While logged in, visit login.php
   - Should auto-redirect without showing form
   - ✅ Verify no double login

5. **Check Browser Developer Tools**
   - Open DevTools → Application → Cookies
   - Verify `sso_token` cookie exists
   - Domain should be `.veerl.es`
   - Secure flag should be ✅
   - HttpOnly flag should be ✅

---

### Step 9: Monitor for Issues
**On production server (optional):**

```bash
# Watch error logs in real-time
tail -f /path/to/web/error_log
# or
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/nginx/error.log
```

**Watch for:**
- PHP errors
- Database connection errors
- JWT decode failures
- Cookie setting issues

---

### Step 10: Post-Deployment Cleanup
**On your local machine:**

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/loginveerles

# Remove the test user creation script
rm create_test_user.php

# Commit final cleanup
git add -A
git commit -m "Remove test user creation script"
git push origin refactor/code-improvements
```

**If everything works on production:**
```bash
# Merge refactor branch to main
git checkout main
git merge refactor/code-improvements
git push origin main
```

---

## ✅ Deployment Success Checklist

- [ ] Database backed up
- [ ] Database migration completed successfully
- [ ] Code pulled from git
- [ ] .env file configured with correct settings
- [ ] JWT cookie domain set to .veerl.es
- [ ] COOKIE_SECURE=true in production
- [ ] File permissions correct
- [ ] Admin login works
- [ ] Regular user login works (redirects to index.php)
- [ ] Admin panel accessible to admins only
- [ ] Logout works
- [ ] Already-logged-in redirect works
- [ ] No errors in server logs

---

## 🆘 Rollback Plan (If Something Goes Wrong)

**On production server:**

```bash
# Restore database from backup
mysql -u your_db_user -p your_db_name < ~/backups/backup_before_jwt_refactor_*.sql

# Revert code to previous version
git log --oneline  # Find commit hash before refactor
git checkout <previous-commit-hash>

# Or go back to main branch if you deployed from refactor branch
git checkout main
git reset --hard origin/main

# Restart web server (if needed)
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

---

## 📞 Need Help?

If you encounter issues during deployment:
1. Check error logs first
2. Verify .env configuration
3. Test database connection
4. Verify JWT keys exist and are readable
5. Check file permissions

---

## 🎉 Post-Deployment

Once deployed successfully:
1. Monitor logs for 24 hours
2. Test with real users
3. Verify email password reset works (if SMTP configured)
4. Consider setting up monitoring/alerts
5. Document any production-specific configuration

Good luck! 🚀
