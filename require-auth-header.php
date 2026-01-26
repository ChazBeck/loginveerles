<?php
/**
 * Authenticated Header
 * Include this at the top of any app page that requires login
 * It will check authentication and display the header with user avatar
 */

// Initialize authentication
if (!function_exists('jwt_init')) {
    $config = require __DIR__ . '/config.php';
    require_once __DIR__ . '/include/jwt_include.php';
    jwt_init();
}

// Require login - redirect to login page if not authenticated
jwt_require_login();

// Get user data including custom avatar
$user = jwt_get_user();
$userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

// Get custom avatar from database
$pdo = auth_db();
$avatarStmt = $pdo->prepare('SELECT avatar_url FROM users WHERE id = :id LIMIT 1');
$avatarStmt->execute([':id' => $user['id']]);
$avatarData = $avatarStmt->fetch(PDO::FETCH_ASSOC);

if ($avatarData && !empty($avatarData['avatar_url'])) {
    $userAvatar = '/apps/auth/' . $avatarData['avatar_url'];
} else {
    $userAvatar = 'https://ui-avatars.com/api/?' . http_build_query([
        'name' => $userName,
        'background' => '667eea',
        'color' => 'fff',
        'size' => '128',
        'bold' => 'true'
    ]);
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/apps/auth/assets/images/veerless-logo-mark-sunrise-rgb-1920px-w-144ppi.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.css">
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    </style>
<?php
// Output function to allow additional head content
if (function_exists('app_head_content')) {
    app_head_content();
}
?>
</head>
<body>
<!-- Shared Header with User Avatar -->
<header class="shared-header">
    <div class="header-container">
        <div class="header-logo">
            <a href="/apps/">
                <img src="/apps/auth/assets/images/veerless-logo-sunrise-rgb-1920px-w-144ppi.png" alt="Veerless Logo" class="logo-image" style="height: 50px;">
            </a>
        </div>
        <nav class="header-nav" style="display: flex; gap: 20px; align-items: center; margin-left: auto; margin-right: 20px;">
            <a href="/apps/auth/index.php" style="color: white; text-decoration: none; font-weight: 500; padding: 8px 16px; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='none'">Home</a>
        </nav>
        <div class="header-right">
            <div class="user-avatar" style="display: flex;">
                <img src="<?= htmlspecialchars($userAvatar) ?>" alt="User Avatar" class="avatar-image">
                <span class="user-name"><?= htmlspecialchars($userName) ?></span>
            </div>
            <button class="auth-button" onclick="window.location.href='/apps/auth/logout.php'">
                <span>Logout</span>
            </button>
        </div>
    </div>
</header>
