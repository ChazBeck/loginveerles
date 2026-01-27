# App Development Guide

## Creating New Apps with Favicon Support

All apps created using the standard template will automatically include the Veerl.es favicon in the browser tab.

### Quick Start

1. **Copy the template:**
   ```bash
   cp apps/APP_TEMPLATE.php apps/yourapp/index.php
   ```

2. **Update the pathPrefix:**
   - If your app is in `/apps/yourapp/`, use `../../`
   - If your app is in `/apps/yourapp/pages/`, use `../../../`

3. **Customize your app:**
   ```php
   render_page_head('Your App Name', [
       'pathPrefix' => '../../',
       'additionalCSS' => ['custom-style.css']  // Optional
   ]);
   
   render_app_header([
       'pathPrefix' => '../../',
       'navLinks' => [
           ['Home', '/apps/'],
           ['Your App', '/apps/yourapp/']
       ]
   ]);
   ```

### What You Get Automatically

✅ Favicon in browser tab  
✅ Consistent Veerl.es branding  
✅ Responsive navigation header  
✅ User authentication integration  
✅ User avatar display  
✅ Logout functionality  
✅ Mobile-friendly design  

### Example: URL Shortener App

```php
<?php
require_once __DIR__ . '/../../include/jwt_include.php';
require_once __DIR__ . '/../../include/render_header.php';

jwt_init();
$user = jwt_get_user();

if (!jwt_is_logged_in()) {
    header('Location: /loginveerles/login.php');
    exit;
}

render_page_head('URL Shortener', ['pathPrefix' => '../../']);
render_app_header([
    'pathPrefix' => '../../',
    'navLinks' => [
        ['Home', '/apps/'],
        ['URL Shortener', '/apps/urlshortener/']
    ]
]);
?>

<main class="app-container">
    <h1>URL Shortener</h1>
    <!-- Your app content here -->
</main>

</body>
</html>
```

### Favicon Details

The favicon is automatically loaded from:
```
assets/images/veerless-logo-mark-sunrise-rgb-1920px-w-144ppi.png
```

This is handled by the `render_page_head()` function in `include/render_header.php`.

### Path Prefix Guide

| Your File Location | pathPrefix Value |
|-------------------|------------------|
| `/apps/index.php` | `../` |
| `/apps/myapp/index.php` | `../../` |
| `/apps/myapp/pages/page.php` | `../../../` |
| `/apps/myapp/admin/panel.php` | `../../../` |

### Migrating Old Apps

If you have an existing app using the old `_header.php`, update it to use the new system:

**Before:**
```php
<?php
require '../auth/require_login.php';
require '../apps/_header.php';
?>
```

**After:**
```php
<?php
require_once __DIR__ . '/../../include/jwt_include.php';
require_once __DIR__ . '/../../include/render_header.php';

jwt_init();
if (!jwt_is_logged_in()) {
    header('Location: /loginveerles/login.php');
    exit;
}

render_page_head('App Title', ['pathPrefix' => '../../']);
render_app_header(['pathPrefix' => '../../']);
?>
```

### Additional CSS

To include app-specific styles:

```php
render_page_head('My App', [
    'pathPrefix' => '../../',
    'additionalCSS' => [
        '../../apps/myapp/styles/custom.css',
        'styles/local.css'
    ]
]);
```

### Admin-Only Apps

For admin-only functionality:

```php
jwt_init();
$user = jwt_get_user();

if (!jwt_is_logged_in() || !jwt_is_admin()) {
    header('Location: /loginveerles/');
    exit;
}
```

## Support

For questions about app development, refer to:
- [render_header.php](include/render_header.php) - Header rendering functions
- [APP_TEMPLATE.php](apps/APP_TEMPLATE.php) - Complete working template
- [index.php](index.php) - Example implementation
