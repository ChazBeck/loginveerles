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
.hero {
    max-width: 1200px;
    margin: 40px auto 80px;
    padding: 40px 20px;
    text-align: center;
}
.welcome {
    color: white;
    font-size: 3rem;
    margin-bottom: 15px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.3);
}
.hero p {
    color: white;
    font-size: 1.5rem;
    margin-bottom: 30px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}
.apps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 30px;
    max-width: 1000px;
    margin-left: auto;
    margin-right: auto;
}
.app-card {
    background: rgba(255, 255, 255, 0.95);
    padding: 35px 30px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    text-decoration: none;
    color: var(--color-primary);
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.app-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 12px rgba(0,0,0,0.2);
    text-decoration: none;
}
.app-card h3 {
    margin: 0;
    color: var(--color-secondary);
    font-size: 1.4rem;
}
.login-prompt {
    background: rgba(255, 255, 255, 0.95);
    padding: 50px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    max-width: 500px;
    margin: 60px auto;
    text-align: center;
}
.login-prompt h2 {
    color: var(--color-primary);
    margin-top: 0;
    margin-bottom: 20px;
}
.login-prompt p {
    color: #666;
    text-shadow: none;
    font-size: 1.1rem;
    margin-bottom: 30px;
}
.btn-primary {
    display: inline-block;
    padding: 14px 32px;
    font-size: 1rem;
    margin-top: 10px;
}
</style>

<?php if ($isLoggedIn): ?>
<main class="hero">
  <h1 class="welcome">Welcome, <?=htmlspecialchars($user['first_name'] ?? $user['email'])?></h1>
  <p>Your Applications</p>
  
  <div class="apps-grid">
    <a href="/apps/urlshortener/" class="app-card">
      <h3>URL Shortener</h3>
    </a>
    
    <a href="/apps/pulse/" class="app-card">
      <h3>Pulse and Hours</h3>
    </a>
    
    <?php if (jwt_is_admin()): ?>
    <a href="admin/" class="app-card">
      <h3>Admin Panel</h3>
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
