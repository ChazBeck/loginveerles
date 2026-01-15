<?php
require __DIR__ . '/include/jwt_include.php';

use Firebase\JWT\JWT;

// Make config global accessible
global $config;

// Check if user is already logged in
if (jwt_init() && jwt_is_logged_in()) {
    $user = jwt_get_user();
    // Redirect to appropriate page
    if (isset($_GET['return_to']) && $_GET['return_to']) {
        header('Location: ' . $_GET['return_to']);
    } elseif (strcasecmp($user['role'] ?? '', 'admin') === 0) {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

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
        $stmt = $pdo->prepare('SELECT id, email, password_hash, first_name, last_name, role, is_active FROM users WHERE email = :e LIMIT 1');
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
                $insertSession = $pdo->prepare('INSERT INTO sessions (user_id, jti, created_at, expires_at) 
                                                VALUES (:uid, :jti, FROM_UNIXTIME(:iat), FROM_UNIXTIME(:exp))');
                $insertSession->execute([
                    ':uid' => $user['id'],
                    ':jti' => $jti,
                    ':iat' => $issuedAt,
                    ':exp' => $expiresAt
                ]);
            } catch (Exception $e) {
                error_log("Session insert failed: " . $e->getMessage());
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
  <style>
    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
      background: url('assets/images/v2osk-214954-unsplash.jpg') center/cover fixed;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .auth-form-container {
      background: rgba(255, 255, 255, 0.95);
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 400px;
      margin: 20px;
    }
    .auth-form-container h1 {
      color: #043546;
      margin-top: 0;
      margin-bottom: 30px;
      font-size: 2rem;
      text-align: center;
    }
    .auth-form-container .error {
      background: #fee;
      color: #c33;
      padding: 12px;
      border-radius: 4px;
      margin-bottom: 20px;
      border: 1px solid #fcc;
    }
    .auth-form-container label {
      display: block;
      margin-bottom: 20px;
      color: #043546;
      font-weight: 500;
    }
    .auth-form-container input[type="email"],
    .auth-form-container input[type="password"] {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
      margin-top: 5px;
      box-sizing: border-box;
    }
    .auth-form-container input[type="email"]:focus,
    .auth-form-container input[type="password"]:focus {
      outline: none;
      border-color: #E58325;
    }
    .auth-form-container .checkbox-label {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }
    .auth-form-container input[type="checkbox"] {
      margin-right: 8px;
      width: auto;
    }
    .auth-form-container button[type="submit"] {
      width: 100%;
      background: #E58325;
      color: white;
      border: none;
      padding: 14px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      transition: background 0.2s;
    }
    .auth-form-container button[type="submit"]:hover {
      background: #d67520;
    }
    .auth-form-container .form-footer {
      text-align: center;
      margin-top: 20px;
      color: #666;
    }
    .auth-form-container .form-footer a {
      color: #E58325;
      text-decoration: none;
    }
  </style>
</head>
<body>
<div class="auth-form-container">
  <h1>Login</h1>
  <?php if ($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <form method="post" action="login.php<?php echo isset($_GET['return_to']) ? '?return_to=' . urlencode($_GET['return_to']) : ''; ?>">
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
