# Final Testing Checklist

## ✅ Tests to Complete

### 1. Login Flow (Admin User)
- [ ] Go to http://localhost/loginveerles/login.php
- [ ] Login with charlie@veerless.com / admin123
- [ ] Should redirect to admin panel (admin/index.php)
- [ ] Verify you see user list

### 2. Logout Flow
- [ ] Click "LOG OUT" button in admin panel
- [ ] Should redirect to login.php
- [ ] Try accessing admin/index.php directly
- [ ] Should redirect back to login with return_to parameter

### 3. Return-To Parameter
- [ ] While logged out, visit: http://localhost/loginveerles/admin/edit_user.php?id=1
- [ ] Should redirect to login with return_to parameter
- [ ] Log in with charlie@veerless.com / admin123
- [ ] Should redirect back to edit_user.php?id=1

### 4. Already Logged In
- [ ] While logged in, visit login.php again
- [ ] Should auto-redirect to admin panel (no login form shown)

### 5. Admin User Management
- [ ] In admin panel, click "Edit" on a user
- [ ] Make a change and save
- [ ] Should redirect back to admin panel with success message

### 6. Password Reset Flow (Optional)
- [ ] Log out
- [ ] Click "Forgot password / Set password" on login page
- [ ] Enter email and submit
- [ ] Check if form processes (may need email configured)

### 7. Session Expiry
- [ ] Check database: SELECT * FROM sessions WHERE revoked_at IS NULL;
- [ ] Verify JWT expiry is set to 60 minutes
- [ ] Old sessions should be revocable

## 🧹 Files to Clean Up After Testing

### Debug/Test Files to Delete:
- check_schema.php
- check_sessions_schema.php
- fix_session_token.php
- run_migration.php
- simple_test.php
- test_complete_auth.php
- test_login_flow.php
- test_redirect.php
- test_user.php

### Keep These Files:
- sql/update_sessions_for_jwt.sql (migration for production)
- All other production files

## 🚀 Production Deployment Checklist

### Before deploying to tools.veerl.es:
1. [ ] Remove all debug/test PHP files
2. [ ] Remove debug logging from login.php (error_log statements)
3. [ ] Remove red debug banner from login.php
4. [ ] Run migration: sql/update_sessions_for_jwt.sql on production DB
5. [ ] Update .env.production with correct JWT_COOKIE_DOMAIN
6. [ ] Test on production server
7. [ ] Clear old sessions from database
