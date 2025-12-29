<?php
// Shared header include — include after authentication is initialized
$user = auth_get_user();
$is_admin = $user && ($user['role'] === 'Admin');
?>
<div class="site-header">
  <div class="container">
    <a href="index.php"><img class="site-logo" src="assets/logo.png" alt="Site logo"></a>
    <nav>
      <ul>
        <li><a href="index.php">HOME</a></li>
        <?php if ($is_admin): ?>
        <li><a href="auth/admin/">ADMIN</a></li>
        <?php endif; ?>
        <li><a href="auth/logout.php">LOG OUT</a></li>
      </ul>
    </nav>
  </div>
</div>
