<?php
require __DIR__ . '/include/auth_include.php';
auth_init();
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!auth_verify_csrf($token)) {
        $message = 'Invalid form submission (CSRF)';
    } else {
        $email = $_POST['email'] ?? '';
        $res = auth_request_password_reset($email);
        if ($res === true) {
            $message = 'If that email exists, a reset link has been sent.';
        } elseif (is_string($res) && filter_var($res, FILTER_VALIDATE_URL)) {
            // Local fallback: show reset URL so admin can provision accounts when mail isn't configured.
            $message = 'Email delivery failed or is not configured. Use the link below to set the password (visible only on this server):';
            $resetLink = $res;
        } else {
            $message = 'If that email exists, a reset link has been sent.';
        }
    }
}
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Password Reset</title>
  <link rel="stylesheet" href="../assets/styles.css">
  <link rel="stylesheet" href="../assets/auth-forms.css">
</head>
<body>
  <div class="auth-form-container">
    <h1>Password Reset</h1>
    <?php if ($message): ?>
      <div class="message"><?=htmlspecialchars($message)?></div>
    <?php endif; ?>
    <?php if (!empty($resetLink)): ?>
      <div class="reset-link">
        <a href="<?=htmlspecialchars($resetLink)?>">Set password (provisioning link)</a>
        <div><small>Note: this link is shown because email sending failed or isn't configured.</small></div>
      </div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
      <label for="email">Email Address</label>
      <input type="email" id="email" name="email" autocomplete="email" required>
      <button type="submit">Send Reset Link</button>
    </form>
    <div class="back-link">
      <a href="login.php">Back to Login</a>
    </div>
  </div>
</body>
</html>
