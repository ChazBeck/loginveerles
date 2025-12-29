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
  <link rel="stylesheet" href="../assets/auth-forms.css">
</head>
<body>
  <div class="auth-form-container">
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
