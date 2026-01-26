<?php
/**
 * Shared Header Rendering Component
 * Eliminates duplication across all pages
 */

/**
 * Get user avatar URL with fallback to UI Avatars
 * 
 * @param int $userId User ID
 * @param string $userName User's display name for fallback
 * @param string $pathPrefix Path prefix for avatar URLs (e.g., '../' for admin pages)
 * @return string Avatar URL
 */
function auth_get_user_avatar($userId, $userName = '', $pathPrefix = '') {
    static $cache = [];
    
    // Return cached value if available
    if (isset($cache[$userId])) {
        return $cache[$userId];
    }
    
    $pdo = auth_db();
    $stmt = $pdo->prepare('SELECT avatar_url FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row && !empty($row['avatar_url'])) {
        // Custom avatar exists
        $avatar = $pathPrefix . 'assets/uploads/avatars/' . basename($row['avatar_url']);
    } else {
        // Fallback to UI Avatars
        $avatar = 'https://ui-avatars.com/api/?' . http_build_query([
            'name' => $userName,
            'background' => '667eea',
            'color' => 'fff',
            'size' => '128',
            'bold' => 'true'
        ]);
    }
    
    $cache[$userId] = $avatar;
    return $avatar;
}

/**
 * Render application header with authentication state
 * 
 * @param array $options Configuration options:
 *   - pathPrefix: Path prefix for assets (default: '')
 *   - homeUrl: URL for Home button (default: 'index.php')
 *   - showNav: Show navigation links (default: true)
 *   - navLinks: Array of ['label', 'url'] for nav items (default: [['Home', 'index.php']])
 */
function render_app_header($options = []) {
    // Extract options with defaults
    $pathPrefix = $options['pathPrefix'] ?? '';
    $homeUrl = $options['homeUrl'] ?? 'index.php';
    $showNav = $options['showNav'] ?? true;
    $navLinks = $options['navLinks'] ?? [['Home', $homeUrl]];
    
    // Get authentication state
    $isLoggedIn = jwt_is_logged_in();
    $user = $isLoggedIn ? jwt_get_user() : null;
    
    // Prepare user data if logged in
    if ($isLoggedIn && $user) {
        $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $userAvatar = auth_get_user_avatar($user['id'], $userName, $pathPrefix);
    }
    
    $logoPath = $pathPrefix . 'assets/images/veerless-logo-sunrise-rgb-1920px-w-144ppi.png';
    $logoutUrl = $pathPrefix . 'logout.php';
    $loginUrl = $pathPrefix . 'login.php';
    
    // Render header HTML
    ?>
<!-- Shared Header -->
<header class="shared-header">
    <div class="header-container">
        <div class="header-logo">
            <a href="/apps/">
                <img src="<?= htmlspecialchars($logoPath) ?>" alt="Veerless Logo" class="logo-image">
            </a>
        </div>
        <?php if ($showNav && !empty($navLinks)): ?>
        <nav class="header-nav">
            <?php foreach ($navLinks as $link): ?>
            <a href="<?= htmlspecialchars($link[1]) ?>"><?= htmlspecialchars($link[0]) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
        <div class="header-right">
            <?php if ($isLoggedIn): ?>
            <div class="user-avatar">
                <img src="<?= htmlspecialchars($userAvatar) ?>" alt="User Avatar" class="avatar-image">
                <span class="user-name"><?= htmlspecialchars($userName) ?></span>
            </div>
            <button class="auth-button" onclick="window.location.href='<?= htmlspecialchars($logoutUrl) ?>'">
                <span>Logout</span>
            </button>
            <?php else: ?>
            <button class="auth-button" onclick="window.location.href='<?= htmlspecialchars($loginUrl) ?>'">
                <span>Login</span>
            </button>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php
}

/**
 * Render HTML page head with common assets
 * 
 * @param string $title Page title
 * @param array $options Options:
 *   - pathPrefix: Path prefix for assets
 *   - additionalCSS: Array of additional CSS file paths
 *   - includeAdmin: Include admin.css (default: false)
 */
function render_page_head($title, $options = []) {
    $pathPrefix = $options['pathPrefix'] ?? '';
    $additionalCSS = $options['additionalCSS'] ?? [];
    $includeAdmin = $options['includeAdmin'] ?? false;
    
    $faviconPath = $pathPrefix . 'assets/images/veerless-logo-mark-sunrise-rgb-1920px-w-144ppi.png';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($faviconPath) ?>">
    
    <!-- External CSS -->
    <link rel="stylesheet" href="<?= $pathPrefix ?>assets/css/base.css">
    <link rel="stylesheet" href="<?= $pathPrefix ?>assets/css/components.css">
    <link rel="stylesheet" href="<?= $pathPrefix ?>assets/css/header.css">
    <?php if ($includeAdmin): ?>
    <link rel="stylesheet" href="<?= $pathPrefix ?>assets/css/admin.css">
    <?php endif; ?>
    <?php foreach ($additionalCSS as $cssFile): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssFile) ?>">
    <?php endforeach; ?>
</head>
<body>
<?php
}
