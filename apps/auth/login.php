<?php
require __DIR__ . '/include/auth_include.php';
auth_init();
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $token = $_POST['csrf_token'] ?? '';
    if (!auth_verify_csrf($token)) {
        $error = 'Invalid form submission (CSRF)';
    } else {
        $email = $_POST['email'] ?? '';
        $pass = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        list($ok, $msg) = auth_login($email, $pass, $remember);
        if ($ok) {
            // Validate redirect URL to prevent open redirect vulnerability
            $app_base = auth_get_app_base_path();
            $return = $_GET['return_to'] ?? $app_base . '/index.php';
            // Whitelist allowed redirect paths (relative URLs only)
            $allowed_paths = [
                $app_base . '/index.php',
                $app_base . '/',
                $app_base . '/auth/',
                $app_base . '/admin/',
                $app_base . '/pulse/',
                $app_base . '/url_shortener/',
            ];
            // Check if redirect starts with any allowed path
            $is_valid = false;
            foreach ($allowed_paths as $path) {
                if (strpos($return, $path) === 0) {
                    $is_valid = true;
                    break;
                }
            }
            // Fallback to default if invalid
            if (!$is_valid) {
                $return = $app_base . '/index.php';
            }
            header('Location: ' . $return);
            exit;
        } else {
            $error = $msg ?: 'Login failed';
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Veerless Tools</title>
  <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
<?php 
// Include shared header (modified for login page - no logout link)
?>
<header class="site-header">
  <div class="container">
    <img src="../assets/logo.png" alt="Veerless Logo" class="site-logo">
    <nav>
      <ul>
        <li><a href="../index.php">HOME</a></li>
      </ul>
    </nav>
  </div>
</header>
<main style="background: url('../assets/images/getty-images-cyPdvGd-r10-unsplash (1).jpg') no-repeat center center/cover; min-height: calc(100vh - 80px); display: flex; align-items: center; padding: 20px 0;">
  <div class="login-form">
    <h1 style="margin-top: 0; margin-bottom: 16px; font-size: 1.5rem;">Sign In</h1>
    <?php if ($error): ?><div style="color:#E53935;margin-bottom:16px;"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" autocomplete="email" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" autocomplete="current-password" required>
      </div>
      <div class="form-group" style="display:flex;align-items:center;">
        <input type="checkbox" name="remember" id="remember" style="width:auto;margin-right:8px;">
        <label for="remember" style="margin:0;text-transform:none;font-weight:normal;">Remember me</label>
      </div>
      <button type="submit">Sign in</button>
    </form>
    <p style="margin-top:12px;margin-bottom:0;text-align:center;font-size:0.9rem;"><a href="password_reset_request.php" style="color:#E58325;text-decoration:none;">Forgot password / Set password</a></p>
  </div>
</main>
</body>
</html>
