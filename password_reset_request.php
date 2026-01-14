<?php
require __DIR__ . '/include/jwt_include.php';
jwt_init();
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
<html><head><meta charset="utf-8"><title>Password reset</title></head>
<body>
<h1>Password reset / Set password</h1>
<?php if ($message): ?><div><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if (!empty($resetLink)): ?>
  <div><a href="<?=htmlspecialchars($resetLink)?>">Set password (provisioning link)</a></div>
  <div><small>Note: this link is shown because email sending failed or isn't configured on this server.</small></div>
<?php endif; ?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
  <label>Email: <input type="email" name="email" autocomplete="email" required></label><br>
  <button type="submit">Send reset link</button>
</form>
</body></html>
