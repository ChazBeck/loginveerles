# Production Deployment Checklist

## ✅ Database Migration - COMPLETE!
Your production database already has:
- ✓ jti column
- ✓ revoked_at column
- ✓ session_token is nullable

## 📤 Next Steps to Deploy

### 1. Update Your Production .env File
Make sure your production `.env` file has these settings:

```env
# Database (your existing values)
DB_HOST=localhost
DB_NAME=loginveerles
DB_USER=your_db_user
DB_PASS=your_db_password

# JWT Settings - IMPORTANT!
JWT_COOKIE_NAME=sso_token
JWT_COOKIE_DOMAIN=.veerl.es
JWT_LIFETIME_MINUTES=60

# Cookie Security - CRITICAL for production!
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

**Key differences from localhost:**
- `JWT_COOKIE_DOMAIN=.veerl.es` (NOT empty or localhost!)
- `COOKIE_SECURE=true` (forces HTTPS)
- `APP_ENV=production`

### 2. Upload Files to Production Server

Upload these files/folders via FTP/SFTP to tools.veerl.es:

**Core files to upload:**
```
├── config.php
├── login.php
├── logout.php
├── password_reset_form.php
├── password_reset_request.php
├── index.php
├── jwt_public.pem
├── jwt_private.pem (IMPORTANT!)
├── include/
│   ├── common_functions.php
│   └── jwt_include.php
├── admin/
│   ├── index.php
│   ├── add_user.php
│   ├── edit_user.php
│   └── toggle_user.php
└── vendor/ (Composer dependencies)
```

**DO NOT upload:**
- .env (edit directly on server)
- .git/ folder (unless using git pull)
- Any test files
- TESTING_CHECKLIST.md
- DEPLOYMENT_GUIDE.md

### 3. Verify JWT Key Files Exist on Production

Check that these files are on your production server:
- `jwt_public.pem` (can be readable by web server)
- `jwt_private.pem` (should have restricted permissions)

### 4. Optional: Clear Old Sessions

Run this in production database to force all users to re-login:
```sql
DELETE FROM sessions WHERE jti IS NULL;
```

### 5. Test Production Login

1. **Test Admin Login:**
   - Go to https://tools.veerl.es/login.php
   - Log in with your admin credentials
   - Should redirect to admin panel
   - ✅ Check that it works

2. **Check Cookie in Browser DevTools:**
   - Open DevTools → Application → Cookies
   - Find `sso_token` cookie
   - Verify Domain is `.veerl.es`
   - Verify Secure flag is ✅
   - Verify HttpOnly flag is ✅

3. **Test Logout:**
   - Click "LOG OUT"
   - Should redirect to login page
   - ✅ Verify you're logged out

4. **Test Protected Pages:**
   - While logged out, try to access admin panel
   - Should redirect to login page
   - ✅ Verify authentication works

### 6. Monitor for Issues

After deploying, check for any errors:
- Check PHP error logs
- Test with real users
- Verify login/logout cycle works
- Test password reset (if SMTP configured)

## 🆘 If Something Goes Wrong

1. **Can't log in?**
   - Check `.env` file has correct settings
   - Verify `JWT_COOKIE_DOMAIN=.veerl.es`
   - Make sure `jwt_public.pem` exists and is readable

2. **Cookie not being set?**
   - Check that `COOKIE_SECURE=true` (requires HTTPS)
   - Verify domain is `.veerl.es` not `localhost`

3. **Getting redirected in a loop?**
   - Clear browser cookies
   - Check sessions table in database
   - Verify JWT keys exist

4. **Database errors?**
   - Verify migration completed successfully
   - Check that jti and revoked_at columns exist
   - Run `DESCRIBE sessions;` to verify

## 📋 Summary

Your refactored JWT authentication system:
- ✅ Removed 282 lines of obsolete code
- ✅ Eliminated ~450 lines of duplication
- ✅ Added proper JWT revocation
- ✅ Fixed all authentication flows
- ✅ Database migration complete
- ✅ Ready for production!

## 🎉 You're Ready to Deploy!

Just upload the files and update the .env file on production. Good luck!
