<?php
/**
 * Complete Header with SSO Integration
 * Include this file at the top of your application pages
 * It handles both authentication and header display
 */

// Initialize authentication if not already done
if (!function_exists('jwt_init')) {
    $config = require __DIR__ . '/config.php';
    require_once __DIR__ . '/include/jwt_include.php';
    jwt_init();
}

// Get user data
$user = jwt_get_user();
$isLoggedIn = jwt_is_logged_in();

// Prepare user display data
if ($isLoggedIn) {
    $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    $userAvatar = 'https://ui-avatars.com/api/?' . http_build_query([
        'name' => $userName,
        'background' => '667eea',
        'color' => 'fff',
        'size' => '128',
        'bold' => 'true'
    ]);
}
?>
<!-- Shared Header CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.css">

<!-- Shared Header -->
<header id="shared-header" class="shared-header">
    <div class="header-container">
        <div class="header-logo">
            <a href="/apps/" id="logo-link">
                <img src="/apps/auth/assets/logo.png" alt="Logo" id="header-logo-img" class="logo-image">
                <span class="logo-text" id="logo-text">Veerl.es Tools</span>
            </a>
        </div>
        <div class="header-right">
            <?php if ($isLoggedIn): ?>
                <div class="user-avatar" id="user-avatar" style="display: flex;">
                    <img src="<?= htmlspecialchars($userAvatar) ?>" alt="User Avatar" id="avatar-img" class="avatar-image">
                    <span class="user-name" id="user-name"><?= htmlspecialchars($userName) ?></span>
                </div>
                <button class="auth-button" id="auth-button" onclick="window.location.href='/apps/auth/logout.php'">
                    <span id="auth-button-text">Logout</span>
                </button>
            <?php else: ?>
                <div class="user-avatar" id="user-avatar" style="display: none;">
                    <img src="" alt="User Avatar" id="avatar-img" class="avatar-image">
                    <span class="user-name" id="user-name"></span>
                </div>
                <button class="auth-button" id="auth-button" onclick="window.location.href='/apps/auth/login.php?return_to=<?= urlencode($_SERVER['REQUEST_URI']) ?>'">
                    <span id="auth-button-text">Login</span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</header>
