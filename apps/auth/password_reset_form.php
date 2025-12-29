<?php
require __DIR__ . '/include/auth_include.php';
$token = $_GET['token'] ?? ($_POST['token'] ?? null);
auth_init();
// If a reset token is present, ensure any existing session is revoked so the page
// forces the user to set a new password instead of silently keeping them logged in.
if ($token && auth_get_user()) {
    auth_logout();
}
$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? null;
    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['password_confirm'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    if (!auth_verify_csrf($csrf)) {
        $error = 'Invalid form submission (CSRF)';
    } elseif (!$token) $error = 'Missing token';
    elseif ($pw === '' || $pw !== $pw2) $error = 'Passwords must match and not be empty';
    else {
        // Validate password policy
        list($valid_pw, $pw_error) = auth_validate_password($pw);
        if (!$valid_pw) {
            $error = $pw_error;
        } elseif (auth_complete_password_reset($token, $pw)) {
            $success = 'Password set. You may now <a href="login.php">login</a>.';
        } else {
            $error = 'Invalid or expired token';
        }
    }
}
$valid = $token ? auth_verify_reset_token($token) : false;
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Set Password</title>
  <link rel="stylesheet" href="../assets/styles.css">
  <style>
    body {
      background: url('../assets/images/getty-images-cyPdvGd-r10-unsplash (1).jpg') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .password-form {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 8px;
      padding: 20px;
      max-width: 340px;
      width: 100%;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .password-form h1 {
      font-size: 1.5rem;
      margin-bottom: 12px;
    }
    .error {
      padding: 10px;
      margin-bottom: 12px;
      border-radius: 4px;
      background: #ffebee;
      border: 1px solid #f44336;
      color: #c62828;
    }
    .success {
      padding: 10px;
      margin-bottom: 12px;
      border-radius: 4px;
      background: #e8f5e9;
      border: 1px solid #4caf50;
      color: #2e7d32;
    }
    .password-requirements {
      font-size: 0.85rem;
      color: #666;
      margin-bottom: 12px;
      padding: 8px;
      background: #f5f5f5;
      border-radius: 4px;
    }
  </style>
</head>
<body>
  <div class="password-form">
    <h1>Set Password</h1>
    <?php if ($error): ?>
      <div class="error"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success"><?= $success ?></div>
    <?php else: ?>
      <?php if (!$token || !$valid): ?>
        <div class="error">Invalid or expired token. <a href="password_reset_request.php">Request a new reset link</a>.</div>
      <?php else: ?>
        <div class="password-requirements">
          <strong>Password requirements:</strong><br>
          • At least 8 characters<br>
          • One uppercase letter<br>
          • One lowercase letter<br>
          • One number<br>
          • One special character
        </div>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
          <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">
          <label for="password">New Password</label>
          <input type="password" id="password" name="password" autocomplete="new-password" required>
          <label for="password_confirm">Confirm Password</label>
          <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
          <button type="submit">Set Password</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
