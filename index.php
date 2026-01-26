<?php
require __DIR__ . '/include/jwt_include.php';
jwt_init();
$user = jwt_get_user();
$isLoggedIn = jwt_is_logged_in();

// Prepare user display data for header
if ($isLoggedIn) {
    $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    
    // Get custom avatar from database
    $pdo = auth_db();
    $avatarStmt = $pdo->prepare('SELECT avatar_url FROM users WHERE id = :id LIMIT 1');
    $avatarStmt->execute([':id' => $user['id']]);
    $avatarData = $avatarStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($avatarData && !empty($avatarData['avatar_url'])) {
        $userAvatar = 'assets/uploads/avatars/' . basename($avatarData['avatar_url']);
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
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Apps Home - Veerl.es Tools</title>
  <link rel="icon" type="image/png" href="assets/images/veerless-logo-mark-sunrise-rgb-1920px-w-144ppi.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.css">
  <style>
    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: url('assets/images/v2osk-214954-unsplash.jpg') center/cover fixed;
      min-height: 100vh;
    }
    .hero {
      max-width: 1200px;
      margin: 60px auto;
      padding: 40px 20px;
      text-align: center;
    }
    .welcome {
      color: white;
      font-size: 3rem;
      margin-bottom: 20px;
      text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    .hero p {
      color: white;
      font-size: 1.5rem;
      margin-bottom: 40px;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    .apps-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 40px;
    }
    .app-card {
      background: rgba(255, 255, 255, 0.95);
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      text-decoration: none;
      color: #043546;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .app-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 12px rgba(0,0,0,0.2);
    }
    .app-card h3 {
      margin: 0 0 10px 0;
      color: #E58325;
      font-size: 1.5rem;
    }
    .app-card p {
      margin: 0;
      color: #666;
      font-size: 1rem;
      text-shadow: none;
    }
    .login-prompt {
      background: rgba(255, 255, 255, 0.95);
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      max-width: 500px;
      margin: 60px auto;
    }
    .login-prompt h2 {
      color: #043546;
      margin-top: 0;
    }
    .login-prompt p {
      color: #666;
      text-shadow: none;
      font-size: 1rem;
    }
    .btn {
      display: inline-block;
      padding: 12px 32px;
      background: #E58325;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-weight: 600;
      margin-top: 20px;
      transition: background 0.2s;
    }
    .btn:hover {
      background: #d67520;
    }
  </style>
</head>
<body>
<!-- Shared Header -->
<header class="shared-header">
    <div class="header-container">
        <div class="header-logo">
            <a href="/apps/">
                <img src="assets/images/veerless-logo-sunrise-rgb-1920px-w-144ppi.png" alt="Veerless Logo" class="logo-image" style="height: 50px;">
            </a>
        </div>
        <nav class="header-nav" style="display: flex; gap: 20px; align-items: center; margin-left: auto; margin-right: 20px;">
            <a href="index.php" style="color: white; text-decoration: none; font-weight: 500; padding: 8px 16px; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='none'">Home</a>
        </nav>
        <div class="header-right">
            <?php if ($isLoggedIn): ?>
                <div class="user-avatar" style="display: flex;">
                    <img src="<?= htmlspecialchars($userAvatar) ?>" alt="User Avatar" class="avatar-image">
                    <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                </div>
                <button class="auth-button" onclick="window.location.href='logout.php'">
                    <span>Logout</span>
                </button>
            <?php else: ?>
                <div class="user-avatar" style="display: none;">
                    <img src="" alt="User Avatar" class="avatar-image">
                    <span class="user-name"></span>
                </div>
                <button class="auth-button" onclick="window.location.href='login.php'">
                    <span>Login</span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ($isLoggedIn): ?>
<main class="hero">
  <h1 class="welcome">Welcome, <?=htmlspecialchars($user['first_name'] ?? $user['email'])?></h1>
  <p>Your Applications</p>
  
  <div class="apps-grid">
    <a href="/apps/urlshortener/" class="app-card">
      <h3>URL Shortener</h3>
      <p>Create and manage short URLs</p>
    </a>
    
    <a href="/apps/pulse/" class="app-card">
      <h3>Pulse</h3>
      <p>Monitor your services and websites</p>
    </a>
    
    <?php if (jwt_is_admin()): ?>
    <a href="admin/" class="app-card">
      <h3>Admin Panel</h3>
      <p>Manage users and system settings</p>
    </a>
    <?php endif; ?>
  </div>
</main>
<?php else: ?>
<div class="login-prompt">
  <h2>Welcome to Veerl.es Tools</h2>
  <p>Please log in to access your applications.</p>
  <a href="login.php" class="btn">Login</a>
</div>
<?php endif; ?>
</body>
</html>
