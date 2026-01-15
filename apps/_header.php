<?php
// Shared header include — include after authentication is initialized
$user = auth_get_user();
$is_admin = $user && ($user['role'] === 'Admin');

// Determine base path based on current file location using __FILE__
$current_file = __FILE__;
$header_dir = dirname($current_file); // This is /apps/

// Check if we're in admin folder by looking at the script that included this
$included_from = dirname($_SERVER['SCRIPT_FILENAME']);
if (strpos($included_from, '/admin') !== false) {
    // We're in the auth/admin subfolder
    $base = '../..';
    $auth = '..';
    $logo = '../../assets/logo.png';
} elseif (strpos($included_from, '/auth') !== false) {
    // We're in the auth folder
    $base = '..';
    $auth = '.';
    $logo = '../assets/logo.png';
} else {
    // We're in the main apps folder
    $base = '.';
    $auth = 'auth';
    $logo = 'assets/logo.png';
}
?>
<div class="site-header">
  <div class="container">
    <a href="<?=$base?>/index.php"><img class="site-logo" src="<?=$logo?>" alt="Site logo"></a>
    <nav>
      <ul>
        <li><a href="<?=$base?>/index.php">HOME</a></li>
        <?php if ($is_admin): ?>
        <li><a href="<?=$auth?>/admin/">ADMIN</a></li>
        <?php endif; ?>
        <li><a href="<?=$auth?>/logout.php">LOG OUT</a></li>
      </ul>
    </nav>
  </div>
</div>
