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

// Load header rendering functions
require_once __DIR__ . '/include/render_header.php';

// Initialize JWT for header display
jwt_init();

render_page_head('Login - Tools');
render_app_header(['showNav' => false]);
?>

<style>
body { display: flex; flex-direction: column; }
.login-content {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<div class="login-content">
<div class="form-container">
  <h1 style="text-align: center;">Login</h1>
  <?php if ($error): ?><div class="alert alert-error"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <form method="post" action="login.php<?php echo isset($_GET['return_to']) ? '?return_to=' . urlencode($_GET['return_to']) : ''; ?>">
    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
    <div class="form-group">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-input" autocomplete="email" required>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-input" autocomplete="current-password" required>
    </div>
    <div class="form-group">
      <label style="display: flex; align-items: center;">
        <input type="checkbox" name="remember" class="form-checkbox"> Remember me
      </label>
    </div>
    <button type="submit" class="btn btn-primary btn-full">Sign in</button>
  </form>
  <p class="form-footer"><a href="password_reset_request.php">Forgot password / Set password</a></p>
</div>
</div>
</body>
</html>
