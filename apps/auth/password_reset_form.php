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
<html><head><meta charset="utf-8"><title>Set password</title></head>
<body>
<h1>Set password</h1>
<?php if ($error): ?><div style="color:red"><?=htmlspecialchars($error)?></div><?php endif; ?>
<?php if ($success): ?><div style="color:green"><?= $success ?></div><?php else: ?>
<?php if (!$token || !$valid): ?><div>Invalid or expired token. Request a new reset <a href="password_reset_request.php">here</a>.</div><?php else: ?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
  <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">
  <label>New password: <input type="password" name="password" autocomplete="new-password" required></label><br>
  <label>Confirm password: <input type="password" name="password_confirm" autocomplete="new-password" required></label><br>
  <button type="submit">Set password</button>
</form>
<?php endif; ?>
<?php endif; ?>
</body></html>
