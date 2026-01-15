<?php
require __DIR__ . '/include/common_functions.php';

use Firebase\JWT\JWT;

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $token = $_POST['csrf_token'] ?? '';
    if (!auth_verify_csrf($token)) {
        $error = 'Invalid form submission (CSRF)';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        
        // Validate credentials directly
        $pdo = auth_db();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :e LIMIT 1');
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check rate limiting (last 15 minutes, max 10 attempts)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $checkAttempts = $pdo->prepare('SELECT COUNT(*) as c FROM login_attempts 
                                        WHERE (user_email = :e OR ip = :ip) 
                                        AND success = 0 
                                        AND created_at > (NOW() - INTERVAL 15 MINUTE)');
        $checkAttempts->execute([':e' => $email, ':ip' => $ip]);
        $attemptRow = $checkAttempts->fetch(PDO::FETCH_ASSOC);
        $failedAttempts = (int)($attemptRow['c'] ?? 0);
        
        if ($failedAttempts >= 10) {
            $error = 'Too many failed attempts. Try again later.';
        } elseif (!$user || !password_verify($pass, $user['password_hash'])) {
            // Record failed attempt
            $userId = $user['id'] ?? null;
            $logAttempt = $pdo->prepare('INSERT INTO login_attempts (user_id, user_email, ip, success) 
                                         VALUES (:uid, :e, :ip, 0)');
            $logAttempt->execute([':uid' => $userId, ':e' => $email, ':ip' => $ip]);
            $error = 'Invalid email or password';
        } elseif (!$user['is_active']) {
            $error = 'Your account has been disabled';
        } else {
            // Login successful - update last login and log attempt
            $updateLogin = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
            $updateLogin->execute([':id' => $user['id']]);
            
            $logSuccess = $pdo->prepare('INSERT INTO login_attempts (user_id, user_email, ip, success) 
                                         VALUES (:uid, :e, :ip, 1)');
            $logSuccess->execute([':uid' => $user['id'], ':e' => $email, ':ip' => $ip]);
            
            // Generate JWT
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
            
            // Store JWT ID in sessions table for revocation capability
            try {
                $insertSession = $pdo->prepare('INSERT INTO sessions (session_token, user_id, ip, user_agent, created_at) 
                                                VALUES (:jti, :uid, :ip, :ua, NOW())');
                $insertSession->execute([
                    ':jti' => $jti, 
                    ':uid' => $user['id'],
                    ':ip' => $ip,
                    ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
            } catch (Exception $e) {
                // Continue even if session insert fails
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
            
            // Default redirect based on user role
            if (isset($_GET['return_to']) && $_GET['return_to']) {
                $return = $_GET['return_to'];
            } elseif (strcasecmp($user['role'] ?? '', 'admin') === 0) {
                $return = 'admin/index.php';
            } else {
                $return = 'index.php';
            }
            header('Location: ' . $return);
            exit;
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
