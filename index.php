<?php
require __DIR__ . '/include/jwt_include.php';
require __DIR__ . '/include/render_header.php';

jwt_init();
$user = jwt_get_user();
$isLoggedIn = jwt_is_logged_in();

render_page_head('Apps Home - Veerl.es Tools');
render_app_header();
?>

<style>
<style>
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
    color: var(--color-primary);
    transition: transform 0.2s, box-shadow 0.2s;
}
.app-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 12px rgba(0,0,0,0.2);
    text-decoration: none;
}
.app-card h3 {
    margin: 0 0 10px 0;
    color: var(--color-secondary);
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
    text-align: center;
}
.login-prompt h2 {
    color: var(--color-primary);
    margin-top: 0;
}
.login-prompt p {
    color: #666;
    text-shadow: none;
    font-size: 1rem;
}
</style>

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
  <a href="login.php" class="btn btn-primary">Login</a>
</div>
<?php endif; ?>
</body>
</html>
