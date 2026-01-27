<?php
/**
 * Complete Header with SSO Integration
 * Include this file at the top of your application pages
 * It handles both authentication and header display
 * 
 * Usage:
 * require_once __DIR__ . '/../auth/header-with-sso.php';
 * render_sso_head('Your Page Title');
 * render_sso_header();
 */

// Initialize authentication if not already done
if (!function_exists('jwt_init')) {
    $config = require __DIR__ . '/config.php';
    require_once __DIR__ . '/include/jwt_include.php';
    jwt_init();
}

/**
 * Render HTML head with favicon and CSS
 * @param string $title Page title
 */
function render_sso_head($title = 'Veerl.es Tools') {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="icon" type="image/png" href="/apps/auth/assets/images/veerless-logo-mark-sunrise-rgb-1920px-w-144ppi.png">
    <link rel="stylesheet" href="/apps/auth/assets/css/base.css">
    <link rel="stylesheet" href="/apps/auth/assets/css/components.css">
    <link rel="stylesheet" href="/apps/auth/assets/css/header.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.css">
</head>
<body>
<?php
}

/**
 * Render SSO header
 */
function render_sso_header() {
    // Get user data
    $user = jwt_get_user();
    $isLoggedIn = jwt_is_logged_in();

// Prepare user display data
if ($isLoggedIn) {
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
}
?>
<style>
    /* Navigation styling */
    .header-container {
        justify-content: flex-start !important;
        gap: 20px;
    }
    .header-logo {
        margin-right: 20px;
    }
    .header-nav {
        display: flex;
        gap: 20px;
        align-items: center;
    }
    .header-nav a {
        color: #043546 !important;
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        padding: 10px 20px;
        border-radius: 4px;
        transition: all 0.2s;
        background: rgba(229, 131, 37, 0.1);
        white-space: nowrap;
    }
    .header-nav a:hover {
        background: rgba(229, 131, 37, 0.2);
        color: #E58325 !important;
    }
    .header-right {
        margin-left: auto;
    }
</style>

<!-- Shared Header -->
<header id="shared-header" class="shared-header">
    <div class="header-container">
        <div class="header-logo">
            <a href="/apps/" id="logo-link">
                <img src="/apps/auth/assets/images/veerless-logo-sunrise-rgb-1920px-w-144ppi.png" alt="Veerless Logo" id="header-logo-img" class="logo-image" style="height: 50px;">
            </a>
        </div>
        <nav class="header-nav">
            <a href="/apps/auth/index.php">Home</a>
        </nav>
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
<?php
}
?>