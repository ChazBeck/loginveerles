# Shared Header Integration Guide

This guide explains how to integrate your shared header component with the authentication system to show user avatars when logged in.

## 🎯 What We've Created

1. **SSO Check Endpoint** (`/api/auth/check.php`) - Returns user authentication status and data
2. **Configuration File** (`sharedheader-config.js`) - Configures the shared header behavior
3. **PHP Include File** (`sharedheader-include.php`) - Easy PHP integration template
4. **Example HTML** (`example-sharedheader-integration.html`) - Full integration example

## 📋 Setup Steps

### Step 1: Clone/Copy the Shared Header Files

You have two options:

**Option A: Host the files yourself (Recommended)**
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/loginveerles/
git clone https://github.com/ChazBeck/sharedheader.git sharedheader
```

**Option B: Use CDN (simpler but requires internet)**
- Files are loaded from GitHub via CDN
- See the example files for CDN URLs

### Step 2: Verify the SSO Endpoint

The endpoint at `/apps/auth/api/auth/check.php` should now be working. Test it:

1. **When logged out:**
   ```bash
   curl http://localhost/apps/auth/api/auth/check.php
   ```
   Should return:
   ```json
   {"authenticated":false}
   ```

2. **When logged in:**
   - Login to your system first
   - Then curl with cookies or test in browser
   - Should return:
   ```json
   {
     "authenticated": true,
     "name": "John Doe",
     "email": "john@example.com",
     "avatar": "https://ui-avatars.com/api/...",
     "role": "User",
     "isAdmin": false
   }
   ```

### Step 3: Integration Methods

Choose one of these methods to add the shared header to your applications:

#### Method A: PHP Include (Easiest for PHP apps)

```php
<!DOCTYPE html>
<html>
<head>
    <title>My App</title>
</head>
<body>
    <?php require_once __DIR__ . '/sharedheader-include.php'; ?>
    
    <!-- Your page content -->
    <div class="content">
        <h1>My Application</h1>
    </div>
</body>
</html>
```

#### Method B: Manual HTML Integration

```html
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="/sharedheader/header.css">
</head>
<body>
    <header id="shared-header" class="shared-header">
        <!-- Header HTML from sharedheader-include.php -->
    </header>
    
    <div class="content">
        <!-- Your content -->
    </div>
    
    <script src="/apps/auth/sharedheader-config.js"></script>
    <script src="/sharedheader/header.js"></script>
</body>
</html>
```

#### Method C: JavaScript Dynamic Loading

```html
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="/sharedheader/header.css">
</head>
<body>
    <div id="header-container"></div>
    
    <script>
        fetch('/apps/auth/sharedheader-include.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('header-container').innerHTML = html;
            });
    </script>
</body>
</html>
```

### Step 4: Customize Configuration (Optional)

Edit `/apps/auth/sharedheader-config.js` to customize:

```javascript
window.sharedHeaderConfig = {
    logoUrl: '/path/to/your/logo.png',      // Your logo
    logoText: 'My Company',                  // Text next to logo
    logoLink: '/',                           // Where logo links to
    loginUrl: '/apps/auth/login.php',       // Login page
    logoutUrl: '/apps/auth/logout.php',     // Logout endpoint
    ssoCheckUrl: '/apps/auth/api/auth/check.php', // SSO endpoint
    
    // Customize avatar defaults
    defaultAvatar: 'https://ui-avatars.com/api/?name=User&background=667eea&color=fff',
    
    // Custom handlers
    onAvatarClick: (userData) => {
        // What happens when user clicks their avatar
        window.location.href = '/profile.php';
    }
};
```

## 🎨 Customization

### Change Avatar Generation

The default uses UI Avatars service. To customize:

1. **Edit `/apps/auth/api/auth/check.php`:**
   ```php
   // Option 1: Use Gravatar
   'avatar' => 'https://www.gravatar.com/avatar/' . md5($user['email'])
   
   // Option 2: Use your own avatar storage
   'avatar' => '/uploads/avatars/' . $user['id'] . '.jpg'
   
   // Option 3: Keep UI Avatars but change colors
   'avatar' => 'https://ui-avatars.com/api/?' . http_build_query([
       'name' => $user['first_name'] . ' ' . $user['last_name'],
       'background' => 'FF5722',  // Change color
       'color' => 'fff',
       'size' => '128'
   ])
   ```

### Add User Profile Pictures

To store custom avatars:

1. **Add avatar column to database:**
   ```sql
   ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) NULL;
   ```

2. **Update the check.php endpoint:**
   ```php
   'avatar' => !empty($user['avatar_url']) 
       ? $user['avatar_url'] 
       : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'])
   ```

3. **Create profile upload page** to let users upload avatars

## 🔧 Troubleshooting

### Avatar not showing
- Check browser console for errors
- Verify `/apps/auth/api/auth/check.php` returns correct data
- Check that user is actually logged in (JWT cookie present)
- Verify CORS headers if apps are on different domains

### User not staying logged in
- Check JWT cookie is being set with correct domain
- Verify `credentials: 'include'` in JavaScript fetch calls
- Check cookie settings in `/apps/auth/config.php`

### Header not loading
- Check file paths are correct
- Verify CSS and JS files are accessible
- Check browser network tab for 404 errors
- View browser console for JavaScript errors

## 🔐 CORS Configuration (for cross-domain setups)

If your apps are on different subdomains, update `/apps/auth/api/auth/check.php`:

```php
// Replace the wildcard with your specific domain
header('Access-Control-Allow-Origin: https://yourdomain.com');
header('Access-Control-Allow-Credentials: true');
```

And update cookie settings in `config.php`:
```php
'jwt_cookie_domain' => '.veerl.es', // Notice the leading dot
'cookie_samesite' => 'None',
'cookie_secure' => true, // Required for SameSite=None
```

## 📝 Testing

1. **Test the endpoint directly:**
   ```bash
   # Logged out
   curl http://localhost/apps/auth/api/auth/check.php
   
   # Logged in (use browser dev tools to get cookie)
   curl -H "Cookie: sso_token=YOUR_JWT_TOKEN" http://localhost/apps/auth/api/auth/check.php
   ```

2. **Test in browser:**
   - Open `example-sharedheader-integration.html`
   - Should show "Login" button when logged out
   - Login to your system
   - Refresh page - should show avatar and name
   - Click logout - should return to login button

## 🚀 Next Steps

1. Replace the old PHP header (`/apps/_header.php`) with the new shared header
2. Update all your applications to use the shared header
3. Add custom avatar upload functionality if desired
4. Customize colors and styling to match your brand
5. Add admin panel link or other custom menu items

## 📞 Support

If you encounter issues:
1. Check browser console for JavaScript errors
2. Check network tab to see API responses
3. Verify JWT token is present in cookies
4. Test the `/api/auth/check.php` endpoint directly
