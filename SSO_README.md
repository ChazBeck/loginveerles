# JWT-Based SSO Implementation

This authentication system now uses JWT (JSON Web Tokens) for Single Sign-On (SSO) across all internal applications on `tools.veerl.es`.

## Overview

**Authentication Flow:**
1. User visits any app (e.g., `https://tools.veerl.es/apps/pulse/`)
2. If no valid JWT cookie exists, they're redirected to `https://tools.veerl.es/apps/auth/login.php`
3. After successful login, a JWT is issued and set as an HttpOnly cookie named `sso_token`
4. User is redirected back to the original app
5. App validates JWT and grants access
6. All subsequent app visits use the same JWT (true SSO)

**Key Features:**
- **RS256 signing** - Private key for signing (auth server), public key for validation (all apps)
- **60-minute token lifetime** - Configurable via `jwt_lifetime_minutes` in config
- **Cross-app authentication** - One login works for all apps on the domain
- **Stateless validation** - Apps validate JWTs without database queries (fast)
- **Session tracking** - JWT ID stored in database for optional revocation capability
- **Backward compatible** - Functions like `auth_get_user()` still work

## Files Structure

```
apps/auth/
├── jwt_private.pem          # RSA private key (keep secure, never commit!)
├── jwt_public.pem           # RSA public key (distribute to apps)
├── include/
│   ├── jwt_include.php      # New JWT authentication library
│   └── auth_include.php     # Legacy session authentication (still used by login.php)
└── login.php                # Issues JWTs after successful authentication
```

## Configuration

Update `config.php` with these new settings:

```php
'jwt_cookie_name' => 'sso_token',           // JWT cookie name
'jwt_cookie_domain' => 'tools.veerl.es',    // Domain for SSO (change to your domain)
'jwt_lifetime_minutes' => 60,                // JWT expiry (60 minutes recommended)
'cookie_secure' => true,                     // Set to true on HTTPS (production)
'cookie_path' => '/',                        // Cookie path
'cookie_samesite' => 'Lax',                  // SameSite policy
```

## Integrating New Apps

### PHP Apps

**Minimal integration (3 lines):**

```php
<?php
require __DIR__ . '/../auth/include/jwt_include.php';
jwt_init();
jwt_require_login(); // Redirects to login if not authenticated

// Get user data
$user = jwt_get_user();
echo "Welcome " . htmlspecialchars($user['email']);

// Check if admin
if (jwt_is_admin()) {
    echo "You have admin privileges";
}
```

**Available functions:**
- `jwt_init()` - Load and validate JWT from cookie
- `jwt_require_login()` - Redirect to login if not authenticated
- `jwt_get_user()` - Returns user array or null
- `jwt_is_admin()` - Check if user has Admin role
- `jwt_require_admin()` - Redirect if not admin
- `jwt_logout()` - Clear JWT cookie and delete session

**Backward compatible aliases:**
All `auth_*` functions are aliased to `jwt_*` equivalents:
- `auth_get_user()` → `jwt_get_user()`
- `auth_require_login()` → `jwt_require_login()`
- `auth_is_admin()` → `jwt_is_admin()`
- etc.

### Non-PHP Apps (Node.js, Python, etc.)

**Requirements:**
1. Copy `jwt_public.pem` to your app directory
2. Install JWT library for your language
3. Read `sso_token` cookie
4. Validate JWT using public key

**Node.js Example:**

```javascript
const jwt = require('jsonwebtoken');
const fs = require('fs');

const publicKey = fs.readFileSync('./jwt_public.pem');

function authenticateUser(req, res, next) {
    const token = req.cookies.sso_token;
    
    if (!token) {
        return res.redirect('https://tools.veerl.es/apps/auth/login.php?return_to=' + 
            encodeURIComponent(req.originalUrl));
    }
    
    try {
        const user = jwt.verify(token, publicKey, { algorithms: ['RS256'] });
        req.user = {
            id: parseInt(user.sub),
            email: user.email,
            firstName: user.first_name,
            lastName: user.last_name,
            role: user.role
        };
        next();
    } catch (err) {
        // JWT expired or invalid
        res.redirect('https://tools.veerl.es/apps/auth/login.php?return_to=' + 
            encodeURIComponent(req.originalUrl));
    }
}

// Use in routes
app.get('/dashboard', authenticateUser, (req, res) => {
    res.send(`Welcome ${req.user.email}`);
});
```

**Python Flask Example:**

```python
import jwt
from flask import request, redirect
from functools import wraps

with open('jwt_public.pem', 'r') as f:
    PUBLIC_KEY = f.read()

def require_auth(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        token = request.cookies.get('sso_token')
        
        if not token:
            return_to = request.url
            return redirect(f'https://tools.veerl.es/apps/auth/login.php?return_to={return_to}')
        
        try:
            user = jwt.decode(token, PUBLIC_KEY, algorithms=['RS256'])
            request.user = {
                'id': int(user['sub']),
                'email': user['email'],
                'first_name': user['first_name'],
                'last_name': user['last_name'],
                'role': user['role']
            }
            return f(*args, **kwargs)
        except jwt.ExpiredSignatureError:
            return redirect(f'https://tools.veerl.es/apps/auth/login.php?return_to={request.url}')
        except jwt.InvalidTokenError:
            return redirect(f'https://tools.veerl.es/apps/auth/login.php?return_to={request.url}')
    
    return decorated_function

@app.route('/dashboard')
@require_auth
def dashboard():
    return f"Welcome {request.user['email']}"
```

## JWT Payload Structure

The JWT contains these claims:

```json
{
  "iss": "tools.veerl.es",       // Issuer
  "iat": 1705234567,              // Issued at (Unix timestamp)
  "exp": 1705238167,              // Expiration (Unix timestamp)
  "sub": "123",                   // Subject (user ID as string)
  "jti": "abc123...",             // JWT ID (for revocation)
  "email": "user@example.com",    // User email
  "first_name": "John",           // First name
  "last_name": "Doe",             // Last name
  "role": "Admin"                 // Role (Admin or User)
}
```

## Session Revocation

Even though JWTs are stateless, you can still revoke sessions:

1. **Immediate revocation (requires database check):**
   - JWT ID (`jti` claim) is stored in `sessions` table as `session_token`
   - Check database to see if session still exists
   - Delete session record to revoke immediately

2. **Passive revocation (wait for expiry):**
   - JWTs expire after 60 minutes by default
   - Disabled users won't get new JWTs on next login
   - Existing JWTs remain valid until expiry

**Revoke user sessions:**
```bash
php tools/revoke_sessions.php user@example.com
```

## Security Considerations

### Private Key Security
- **NEVER commit `jwt_private.pem` to version control**
- Add to `.gitignore`
- Set permissions: `chmod 600 jwt_private.pem`
- Only the auth server needs the private key

### Public Key Distribution
- Public key (`jwt_public.pem`) can be freely distributed
- Apps only validate signatures, can't create new tokens
- Include public key in app deployments

### Token Lifetime
- 60 minutes balances security and UX
- Shorter = more secure, more re-authentications
- Longer = fewer logins, higher compromise risk
- For sensitive operations, add additional verification

### Cookie Security
- **Production:** Set `cookie_secure` to `true` (HTTPS only)
- **HttpOnly:** Prevents JavaScript access (XSS protection)
- **SameSite=Lax:** Prevents CSRF while allowing normal navigation
- **Domain:** Set to your domain (`tools.veerl.es`)

## Migration Notes

### Migrating Existing Apps from `auth_include.php` to `jwt_include.php`

**Before:**
```php
require __DIR__ . '/../auth/include/auth_include.php';
auth_init();
auth_require_login();
```

**After (Option 1 - New JWT functions):**
```php
require __DIR__ . '/../auth/include/jwt_include.php';
jwt_init();
jwt_require_login();
```

**After (Option 2 - Backward compatible):**
```php
require __DIR__ . '/../auth/include/jwt_include.php';
auth_init();  // Works! Aliased to jwt_init()
auth_require_login();  // Works! Aliased to jwt_require_login()
```

All existing `auth_*` functions are aliased in `jwt_include.php`, so minimal code changes required.

## Troubleshooting

### "Invalid JWT" or Redirect Loop
- Check that `jwt_public.pem` exists and is readable
- Verify cookie domain matches your domain in config
- Check browser console for cookie issues
- Ensure JWT hasn't expired (60 min default)

### "JWT public key not found"
- Run key generation: `openssl genrsa -out jwt_private.pem 2048`
- Extract public: `openssl rsa -in jwt_private.pem -pubout -out jwt_public.pem`
- Set permissions: `chmod 600 jwt_private.pem && chmod 644 jwt_public.pem`

### User stays logged in after disable
- JWTs remain valid until expiry (up to 60 minutes)
- For immediate revocation, check JWT ID in database
- Or wait for token to expire

### Cookie not shared across apps
- Verify `jwt_cookie_domain` is set correctly in `config.php`
- Domain must match actual domain (e.g., `tools.veerl.es`)
- All apps must be on same domain or subdomains
- Check browser's Application/Cookies tab to verify domain

## Testing

1. **Clear all cookies** from your domain
2. Visit any app (e.g., `https://tools.veerl.es/apps/pulse/`)
3. Should redirect to login page
4. Login with valid credentials
5. Should redirect back to Pulse app
6. **Navigate to another app** (e.g., URL Shortener)
7. Should **NOT** require login again (SSO working!)
8. Check browser cookies - should see `sso_token` cookie

## Production Deployment Checklist

- [ ] Set `cookie_secure` to `true` in production config
- [ ] Ensure HTTPS is enabled
- [ ] Add `jwt_private.pem` to `.gitignore`
- [ ] Set proper file permissions on private key (`chmod 600`)
- [ ] Update `jwt_cookie_domain` to production domain
- [ ] Copy `jwt_public.pem` to all app servers
- [ ] Test SSO flow across all apps
- [ ] Verify logout works across all apps
- [ ] Test admin access controls

## Performance

**Database queries per request:**
- **With JWT:** 0 queries (stateless validation)
- **With sessions:** 1-2 queries per request

**Benefits:**
- Faster response times
- Lower database load
- Better scalability
- Apps can be distributed across servers

## Future Enhancements

Potential additions:
- Token refresh endpoint (extend expiry without re-login)
- JWT blacklist table (for immediate revocation)
- OAuth2 endpoints (for third-party integrations)
- API key authentication (for programmatic access)
- Rate limiting per user/JWT
- Audit logging of JWT issuance
