# JWT SSO Quick Reference

## For Developers Adding New Apps

### PHP App (3 lines)
```php
<?php
require __DIR__ . '/../auth/include/jwt_include.php';
jwt_init();
jwt_require_login();

$user = jwt_get_user();
// Done! User is authenticated.
```

### Node.js App
```javascript
const jwt = require('jsonwebtoken');
const publicKey = fs.readFileSync('./jwt_public.pem');

const token = req.cookies.sso_token;
const user = jwt.verify(token, publicKey, { algorithms: ['RS256'] });
```

### Python App
```python
import jwt
with open('jwt_public.pem') as f:
    public_key = f.read()
    
token = request.cookies.get('sso_token')
user = jwt.decode(token, public_key, algorithms=['RS256'])
```

## User Object Structure
```php
[
    'id' => 123,
    'email' => 'user@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'role' => 'Admin', // or 'User'
    'jti' => 'abc123...' // JWT ID for revocation
]
```

## Available Functions

| Function | Description |
|----------|-------------|
| `jwt_init()` | Load and validate JWT from cookie |
| `jwt_require_login()` | Redirect to login if not authenticated |
| `jwt_get_user()` | Get user array or null |
| `jwt_is_admin()` | Check if user is admin |
| `jwt_require_admin()` | Redirect if not admin |
| `jwt_logout()` | Clear JWT and delete session |
| `jwt_csrf_token()` | Get CSRF token for forms |
| `jwt_verify_csrf($token)` | Validate CSRF token |

## Backward Compatible Aliases

All these work too:
- `auth_init()` → `jwt_init()`
- `auth_require_login()` → `jwt_require_login()`
- `auth_get_user()` → `jwt_get_user()`
- etc.

## Configuration (config.php)

```php
'jwt_cookie_name' => 'sso_token',
'jwt_cookie_domain' => 'tools.veerl.es', // or 'localhost' for dev
'jwt_lifetime_minutes' => 60,
'cookie_secure' => true, // HTTPS only in production
'cookie_path' => '/',
'cookie_samesite' => 'Lax',
```

## Common Tasks

### Check if User is Logged In
```php
if (jwt_is_logged_in()) {
    // User is authenticated
}
```

### Get User Email
```php
$user = jwt_get_user();
echo $user['email'];
```

### Require Admin Access
```php
jwt_require_admin(); // Redirects if not admin
```

### Logout User
```php
jwt_logout();
header('Location: /apps/auth/login.php');
```

### Add CSRF Protection to Forms
```php
<form method="post">
    <input type="hidden" name="csrf_token" value="<?=jwt_csrf_token()?>">
    <!-- form fields -->
</form>
```

### Validate CSRF
```php
if (!jwt_verify_csrf($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}
```

## Testing SSO

1. Visit app A → redirects to login
2. Login → redirects back to app A (authenticated)
3. Visit app B → **no login required** ✅ SSO working!

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Redirect loop | Check JWT cookie is being set |
| "Invalid JWT" | Clear cookies, check domain config |
| Not logged in | JWT expired (60 min), login again |
| Cookie not shared | Fix `jwt_cookie_domain` in config |

## File Locations

| File | Purpose |
|------|---------|
| `apps/auth/jwt_public.pem` | Public key (copy to apps) |
| `apps/auth/jwt_private.pem` | Private key (auth server only) |
| `apps/auth/include/jwt_include.php` | JWT library |
| `apps/auth/config.php` | Configuration |

## Documentation

- **Full Guide**: [SSO_README.md](SSO_README.md)
- **Testing**: [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)
- **Summary**: [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)

## Production Checklist

- [ ] Set `jwt_cookie_domain` to production domain
- [ ] Set `cookie_secure => true`
- [ ] Enable HTTPS
- [ ] Test SSO across all apps
- [ ] Verify logout works
- [ ] Never commit `jwt_private.pem`

---

**Need help?** Read the full [SSO_README.md](SSO_README.md)
