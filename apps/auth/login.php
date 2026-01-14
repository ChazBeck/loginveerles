<?php
require __DIR__ . '/include/auth_include.php';

use Firebase\JWT\JWT;

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
            // Login successful - now issue JWT
            $user = auth_get_user();
            $jti = bin2hex(random_bytes(32)); // JWT ID for session tracking
            
            // Load private key for JWT signing
            $privateKey = file_get_contents(__DIR__ . '/jwt_private.pem');
            
            // Calculate expiration time
            $jwtLifetime = ($config['jwt_lifetime_minutes'] ?? 60) * 60;
            $issuedAt = time();
            $expiresAt = $issuedAt + $jwtLifetime;
            
            // Create JWT payload
            $payload = [
                'iss' => $_SERVER['HTTP_HOST'] ?? 'tools.veerl.es', // Issuer
                'iat' => $issuedAt, // Issued at
                'exp' => $expiresAt, // Expiration
                'sub' => (string)$user['id'], // Subject (user ID)
                'jti' => $jti, // JWT ID for revocation
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role'],
            ];
            
            // Generate JWT
            $jwt = JWT::encode($payload, $privateKey, 'RS256');
            
            // Update session record with JWT ID for revocation capability
            try {
                $pdo = auth_db();
                $stmt = $pdo->prepare('UPDATE sessions SET session_token = :jti WHERE user_id = :uid ORDER BY created_at DESC LIMIT 1');
                $stmt->execute([':jti' => $jti, ':uid' => $user['id']]);
            } catch (Exception $e) {
                // Continue even if session update fails
            }
            
            // Set JWT cookie
            $cookieName = $config['jwt_cookie_name'] ?? 'sso_token';
            $domain = $config['jwt_cookie_domain'] ?? '';
            $path = $config['cookie_path'] ?? '/';
            $secure = (bool)($config['cookie_secure'] ?? false);
            $sameSite = $config['cookie_samesite'] ?? 'Lax';
            
            setcookie(
                $cookieName,
                $jwt,
                [
                    'expires' => $expiresAt,
                    'path' => $path,
                    'domain' => $domain,
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => $sameSite,
                ]
            );
            
            $return = $_GET['return_to'] ?? '../index.php';
            header('Location: ' . $return);
            exit;
        } else {
            $error = $msg ?: 'Login failed';
        }
    }
}
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Tools</title>
  <link rel="stylesheet" href="../assets/auth-forms.css">
</head>
<body>
<div class="auth-form-container">
  <h1>Login</h1>
  <?php if ($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
    <label>Email
      <input type="email" name="email" autocomplete="email" required>
    </label>
    <label>Password
      <input type="password" name="password" autocomplete="current-password" required>
    </label>
    <label class="checkbox-label">
      <input type="checkbox" name="remember"> Remember me
    </label>
    <button type="submit">Sign in</button>
  </form>
  <p class="form-footer"><a href="password_reset_request.php">Forgot password / Set password</a></p>
</div>
</body>
</html>
