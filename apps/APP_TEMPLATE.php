<?php
/**
 * Template for creating new apps in the Veerl.es ecosystem
 * 
 * This template ensures:
 * - Consistent favicon across all apps
 * - Proper authentication
 * - Unified header/navigation
 * - Responsive design
 * 
 * To use this template:
 * 1. Copy this file to your app directory (e.g., /apps/urlshortener/index.php)
 * 2. Adjust the pathPrefix based on your directory depth
 * 3. Customize the title and navigation links
 * 4. Add your app-specific content in the main section
 */

// Include authentication and rendering functions
require_once __DIR__ . '/../../include/jwt_include.php';
require_once __DIR__ . '/../../include/render_header.php';

// Initialize authentication
jwt_init();
$user = jwt_get_user();
$isLoggedIn = jwt_is_logged_in();

// Redirect to login if not authenticated
if (!$isLoggedIn) {
    header('Location: /loginveerles/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Render page head with favicon
render_page_head('Your App Title', [
    'pathPrefix' => '../../',  // Adjust based on directory depth
    'additionalCSS' => []       // Add app-specific CSS files here
]);

// Render navigation header
render_app_header([
    'pathPrefix' => '../../',   // Adjust based on directory depth
    'navLinks' => [
        ['Home', '/apps/'],
        ['Your App', '/apps/yourapp/']
    ]
]);
?>

<!-- Your app content goes here -->
<main class="app-container">
    <div class="content">
        <h1>Welcome to Your App</h1>
        <p>Hello, <?= htmlspecialchars($user['first_name'] ?? $user['email']) ?>!</p>
        
        <!-- Add your app-specific content here -->
        
    </div>
</main>

<style>
.app-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}
.content {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
</style>

</body>
</html>
