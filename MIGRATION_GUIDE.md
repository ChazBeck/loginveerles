# JWT SSO Migration Complete! 🎉

Your authentication system has been successfully upgraded to JWT-based Single Sign-On (SSO).

## What Changed

### ✅ Implemented

1. **JWT Library Installed** - `firebase/php-jwt` via Composer
2. **RSA Keypair Generated** - Private key for signing, public key for validation
3. **New JWT Authentication Library** - `jwt_include.php` for stateless SSO
4. **Updated Login Flow** - Now issues JWT tokens after successful authentication
5. **Migrated All Apps** - index.php, pulse, url_shortener now use JWT
6. **Updated Admin Pages** - All admin functions use JWT
7. **Password Reset Flow** - Compatible with JWT authentication
8. **Configuration Updated** - New JWT settings in config.php and config.sample.php
9. **Security** - Private key added to .gitignore

### 📁 New Files

- `apps/auth/jwt_private.pem` - RSA private key (NEVER commit to git!)
- `apps/auth/jwt_public.pem` - RSA public key (distribute to apps)
- `apps/auth/include/jwt_include.php` - JWT authentication library
- `apps/auth/SSO_README.md` - Complete SSO documentation
- `apps/auth/MIGRATION_GUIDE.md` - This file

## How It Works Now

1. **User visits any app** → Checks for JWT cookie
2. **No JWT or expired** → Redirects to login page with return URL
3. **User logs in** → JWT issued and set as HttpOnly cookie
4. **Redirected back** → Original app validates JWT and grants access
5. **Visit another app** → Same JWT works (true SSO!)

## Testing Your SSO

### Step 1: Start XAMPP
Make sure Apache and MySQL are running.

### Step 2: Clear Browser Cookies
Delete all cookies for `localhost` to test fresh.

### Step 3: Test the Flow

1. **Visit Pulse app**: http://localhost/loginveerles/apps/pulse/
   - Should redirect to login page
   
2. **Login** with your credentials
   - Should redirect back to Pulse
   - You should be logged in
   
3. **Visit URL Shortener**: http://localhost/loginveerles/apps/url_shortener/
   - Should **NOT** ask for login again
   - This proves SSO is working!
   
4. **Check Browser Cookies**
   - Open DevTools → Application → Cookies → localhost
   - Should see `sso_token` cookie
   
5. **Test Logout**
   - Click logout from any app
   - Visit another app
   - Should require login again

### Step 4: Test Admin Functions

1. Visit: http://localhost/loginveerles/apps/auth/admin/
2. Should work with JWT authentication
3. Try adding a user, editing user details
4. All should function normally

## Configuration for Production

When deploying to `tools.veerl.es`, update `config.php`:

```php
// Change from:
'jwt_cookie_domain' => 'localhost',
'cookie_secure' => false,
'base_url' => 'http://localhost/loginveerles/apps/auth',

// To:
'jwt_cookie_domain' => 'tools.veerl.es',
'cookie_secure' => true,  // IMPORTANT: Enable for HTTPS
'base_url' => 'https://tools.veerl.es/apps/auth',
```

## Backward Compatibility

All existing `auth_*` functions still work! They're aliased to `jwt_*` functions:

```php
// These all work the same:
auth_get_user()      // ✅ Works
auth_require_login() // ✅ Works
auth_is_admin()      // ✅ Works
auth_logout()        // ✅ Works
```

So existing code doesn't break!

## Adding New Apps

### PHP Apps (Easy!)

```php
<?php
require __DIR__ . '/../auth/include/jwt_include.php';
jwt_init();
jwt_require_login();

$user = jwt_get_user();
echo "Welcome " . htmlspecialchars($user['email']);
```

That's it! 3 lines and your app has SSO.

### Non-PHP Apps

See [SSO_README.md](SSO_README.md) for Node.js and Python examples.

## Token Lifetime

- **JWT expires in 60 minutes** (configurable)
- After expiry, user must log in again
- This balances security vs. convenience
- Change `jwt_lifetime_minutes` in config to adjust

## Session Revocation

Even with JWTs, you can revoke sessions:

```bash
php apps/auth/tools/revoke_sessions.php user@example.com
```

This deletes the session from the database. However, the JWT remains valid until it expires (up to 60 minutes). For truly immediate revocation, apps would need to check the database on critical operations.

## Security Notes

### 🔒 Private Key Security

The private key (`jwt_private.pem`) is like a master password:
- ✅ Added to `.gitignore` (won't be committed)
- ✅ Permissions set to 600 (owner read/write only)
- ⚠️ **NEVER** share or commit this file
- ⚠️ If compromised, regenerate both keys immediately

### 🔓 Public Key Distribution

The public key (`jwt_public.pem`) is safe to share:
- ✅ Can be committed to git
- ✅ Distribute to all apps
- ✅ Apps can only validate tokens, not create them

### Production Checklist

- [ ] Update `jwt_cookie_domain` to `tools.veerl.es`
- [ ] Set `cookie_secure` to `true`
- [ ] Ensure HTTPS is enabled
- [ ] Test SSO across all apps
- [ ] Verify admin functions work
- [ ] Test logout clears JWT properly
- [ ] Backup private key securely

## Troubleshooting

### "Cannot find jwt_include.php"
- Check file path in `require` statement
- Use `__DIR__` for relative paths

### "JWT public key not found"
- Ensure `jwt_public.pem` exists in `apps/auth/`
- Check file permissions (should be readable)

### "Invalid JWT" errors
- Check cookie domain matches actual domain
- Verify JWT hasn't expired (check timestamp)
- Clear browser cookies and try again

### Redirect loop
- Verify login.php issues JWT correctly
- Check that `sso_token` cookie is being set
- Inspect browser DevTools → Network → Cookies

### Cookie not shared across apps
- Check `jwt_cookie_domain` in config.php
- Domain must match (e.g., `localhost` or `tools.veerl.es`)
- Path must be `/` for site-wide access
- Use browser DevTools to inspect cookie settings

## Performance Improvements

### Before (Session-based):
- 1-2 database queries per request
- Session lookup on every page load

### After (JWT-based):
- **0 database queries** for authentication
- Stateless validation (just verify signature)
- Much faster, more scalable!

## Questions?

Read the comprehensive guide: [SSO_README.md](SSO_README.md)

Key sections:
- **Integrating New Apps** - PHP and non-PHP examples
- **JWT Payload Structure** - What data is in the token
- **Session Revocation** - How to disable users immediately
- **Security Considerations** - Best practices
- **Production Deployment** - Checklist for going live

## Next Steps

1. ✅ Test SSO locally (follow testing steps above)
2. ✅ Verify all existing apps still work
3. ✅ Test admin functions
4. 📝 Plan deployment to production
5. 📝 Document any custom apps you add
6. 🚀 Deploy to `tools.veerl.es`

**The SSO system is ready to use!** 🎉
