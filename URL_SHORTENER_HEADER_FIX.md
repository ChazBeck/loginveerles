# Fix URL Shortener Header to Show Logged-in User

## Problem
The URL shortener app requires login (SSO working ✅) but the header doesn't show who is logged in.

## Solution
Add the shared header configuration and JavaScript to your URL shortener app.

## Implementation

### Option 1: Use the PHP Include (Easiest)

In your URL shortener's main page (likely `index.php` or similar), add this in the `<head>` section or right after the `<body>` tag:

```php
<?php require_once '/Applications/XAMPP/xamppfiles/htdocs/loginveerles/sharedheader-include.php'; ?>
```

**OR on production server:**
```php
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/apps/auth/sharedheader-include.php'; ?>
```

This single line includes:
- ✅ Header CSS
- ✅ Header HTML structure
- ✅ Configuration JavaScript
- ✅ Shared header JavaScript

### Option 2: Add Manually (If you already have header HTML)

If your URL shortener already has the header HTML but it's not working, you need to add:

**1. Before the closing `</head>` tag:**
```html
<!-- Shared Header CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.css">
```

**2. Before the closing `</body>` tag (or at the bottom of your page):**
```html
<!-- Configure Shared Header (must be before header.js) -->
<script src="/apps/auth/sharedheader-config.js"></script>

<!-- Include Shared Header JavaScript -->
<script src="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.js"></script>
```

## What This Does

1. **Loads the shared header JavaScript** - This checks authentication status
2. **Calls `/apps/auth/api/auth/check.php`** - Gets logged-in user info
3. **Updates the header** - Shows user avatar and name instead of "Login" button
4. **Handles logout** - Clicking logout button works correctly

## Verification

After adding this, when you visit your URL shortener:

**Before (current state):**
```
[VEERLESS Logo] Home [Login]
```

**After (correct state when logged in):**
```
[VEERLESS Logo] Home [👤 John Doe] [Logout]
```

## Testing

1. Visit: `https://tools.veerl.es/apps/urlshortener/`
2. You should see your name and avatar in the header (not "Login" button)
3. Click your avatar - check browser console for user data
4. Click logout - should log you out and redirect

## Troubleshooting

### "Still showing Login button"

**Check browser console** (F12 → Console tab):
- Look for errors loading JavaScript files
- Check if `/apps/auth/api/auth/check.php` is returning user data

**Test the API endpoint directly:**
```bash
curl -b "sso_token=YOUR_TOKEN" https://tools.veerl.es/apps/auth/api/auth/check.php
```

Should return:
```json
{
  "authenticated": true,
  "name": "John Doe",
  "email": "john@example.com",
  ...
}
```

### "JavaScript not loading"

**Check paths are correct:**
- `/apps/auth/sharedheader-config.js` should exist
- CDN should be accessible: `https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.js`

**Check browser Network tab:**
- Are both JavaScript files loading? (200 status)
- Any 404 errors?

### "Avatar showing but name is blank"

Check that `/apps/auth/api/auth/check.php` is returning the user's name:
- View browser console
- Look for the API response
- Verify `first_name` and `last_name` are in the JWT token

## File Locations

```
Production Server:
/apps/auth/
├── sharedheader-config.js           ← Configuration
├── sharedheader-include.php         ← PHP include template  
└── api/
    └── auth/
        └── check.php                ← SSO endpoint

Your URL Shortener:
/apps/urlshortener/
└── index.php                        ← Add the include here
```

## Quick Copy-Paste Solution

**For production (tools.veerl.es):**

Add this to your URL shortener's `index.php` right after the `<body>` tag:

```php
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/apps/auth/sharedheader-include.php'; ?>
```

**For local testing (localhost):**

```php
<?php require_once '/Applications/XAMPP/xamppfiles/htdocs/loginveerles/sharedheader-include.php'; ?>
```

That's it! One line of code fixes the header.

## Alternative: Inline Header (No PHP Include)

If you can't use PHP includes, add this HTML structure:

```html
<!DOCTYPE html>
<html>
<head>
    <title>URL Shortener</title>
    <!-- Shared Header CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.css">
</head>
<body>
    <!-- Shared Header HTML -->
    <header id="shared-header" class="shared-header">
        <div class="header-container">
            <div class="header-logo">
                <a href="/apps/" id="logo-link">
                    <img src="/apps/auth/assets/images/veerless-logo-sunrise-rgb-1920px-w-144ppi.png" 
                         alt="Veerless Logo" style="height: 50px;">
                </a>
            </div>
            <nav class="header-nav">
                <a href="/apps/">Home</a>
            </nav>
            <div class="header-right">
                <div class="user-avatar" id="user-avatar" style="display: none;">
                    <img src="" alt="User Avatar" id="avatar-img" class="avatar-image">
                    <span class="user-name" id="user-name"></span>
                </div>
                <button class="auth-button" id="auth-button">
                    <span id="auth-button-text">Login</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Your URL shortener content here -->

    <!-- Shared Header JavaScript (at bottom of body) -->
    <script src="/apps/auth/sharedheader-config.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.js"></script>
</body>
</html>
```

---

**Need more help?** Check [SHAREDHEADER_INTEGRATION.md](SHAREDHEADER_INTEGRATION.md) for detailed documentation.
