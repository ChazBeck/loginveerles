<?php
// JWT-based SSO authentication library
// Usage: require this file, call jwt_init(), then use jwt_require_login()

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (file_exists(__DIR__ . '/../config.php')) {
    $config = require __DIR__ . '/../config.php';
} else {
    $config = require __DIR__ . '/../config.sample.php';
}

// Load Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// We use PHP session for CSRF tokens only
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = null;
$currentUser = null;

// CSRF helpers (unchanged from auth_include.php)
function jwt_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function jwt_verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function jwt_db() {
    global $pdo, $config;
    if ($pdo) return $pdo;
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    return $pdo;
}

function jwt_get_public_key() {
    $keyPath = __DIR__ . '/../jwt_public.pem';
    if (!file_exists($keyPath)) {
        throw new Exception('JWT public key not found');
    }
    return file_get_contents($keyPath);
}

function jwt_init() {
    global $currentUser, $config;
    
    $cookieName = $config['jwt_cookie_name'] ?? 'sso_token';
    
    if (!isset($_COOKIE[$cookieName]) || empty($_COOKIE[$cookieName])) {
        return false;
    }
    
    try {
        $jwt = $_COOKIE[$cookieName];
        $publicKey = jwt_get_public_key();
        $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));
        
        // Extract user data from JWT claims
        $currentUser = [
            'id' => (int)$decoded->sub,
            'email' => $decoded->email ?? '',
            'first_name' => $decoded->first_name ?? '',
            'last_name' => $decoded->last_name ?? '',
            'role' => $decoded->role ?? 'User',
            'jti' => $decoded->jti ?? null, // JWT ID for revocation
        ];
        
        return true;
    } catch (Exception $e) {
        // JWT validation failed (expired, invalid signature, etc.)
        jwt_clear_cookie();
        return false;
    }
}

function jwt_get_user() {
    global $currentUser;
    return $currentUser;
}

function jwt_is_logged_in() {
    return jwt_get_user() !== null;
}

function jwt_require_login($redirect = true) {
    if (!jwt_get_user()) {
        if ($redirect) {
            global $config;
            $baseUrl = rtrim($config['base_url'] ?? '', '/');
            $returnTo = urlencode($_SERVER['REQUEST_URI']);
            header('Location: ' . $baseUrl . '/login.php?return_to=' . $returnTo);
            exit;
        }
        return false;
    }
    return true;
}

function jwt_is_admin() {
    $u = jwt_get_user();
    return $u && isset($u['role']) && strcasecmp($u['role'], 'admin') === 0;
}

function jwt_require_admin() {
    jwt_require_login();
    if (!jwt_is_admin()) {
        http_response_code(403);
        if (file_exists(__DIR__ . '/../errors/403.php')) {
            require __DIR__ . '/../errors/403.php';
        } else {
            echo 'Forbidden';
        }
        exit;
    }
}

function jwt_clear_cookie() {
    global $config;
    $cookieName = $config['jwt_cookie_name'] ?? 'sso_token';
    $domain = $config['jwt_cookie_domain'] ?? '';
    $path = $config['cookie_path'] ?? '/';
    $secure = (bool)($config['cookie_secure'] ?? false);
    
    setcookie($cookieName, '', time() - 3600, $path, $domain, $secure, true);
    unset($_COOKIE[$cookieName]);
}

function jwt_logout() {
    global $currentUser;
    
    // Delete session from database if we have the JWT ID
    if ($currentUser && isset($currentUser['jti'])) {
        try {
            $pdo = jwt_db();
            $stmt = $pdo->prepare('DELETE FROM sessions WHERE session_token = :jti');
            $stmt->execute([':jti' => $currentUser['jti']]);
        } catch (Exception $e) {
            // Continue with logout even if DB deletion fails
        }
    }
    
    jwt_clear_cookie();
    $currentUser = null;
}

// Compatibility aliases for apps that use auth_* function names
function auth_get_user() { return jwt_get_user(); }
function auth_is_logged_in() { return jwt_is_logged_in(); }
function auth_require_login($redirect = true) { return jwt_require_login($redirect); }
function auth_is_admin() { return jwt_is_admin(); }
function auth_require_admin() { return jwt_require_admin(); }
function auth_logout() { return jwt_logout(); }
function auth_csrf_token() { return jwt_csrf_token(); }
function auth_verify_csrf($token) { return jwt_verify_csrf($token); }

// Database helper for admin pages
function auth_db() { return jwt_db(); }

// Mail sending helper (from auth_include.php)
function auth_smtp_send($to, $subject, $body, $from) {
    global $config;
    $smtp = $config['smtp'] ?? [];

    if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') && !empty($smtp['host'])) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->Port = $smtp['port'] ?? 587;
            $secure = strtolower($smtp['secure'] ?? 'tls');
            if ($secure === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }
            $mail->SMTPAuth = !empty($smtp['username']);
            if ($mail->SMTPAuth) {
                $mail->Username = $smtp['username'];
                $mail->Password = $smtp['password'];
            }
            $mail->Timeout = $smtp['timeout'] ?? 30;
            $mail->setFrom($from);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $body;
            $mail->send();
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            return false;
        }
    }

    $headers = 'From: ' . $from . "\r\n" . 'Content-Type: text/plain; charset=UTF-8\r\n';
    return mail($to, $subject, $body, $headers);
}

// Password reset functions (from auth_include.php)
function auth_request_password_reset($email) {
    $pdo = jwt_db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
    $stmt->execute([':e' => $email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) return false;
    $token = bin2hex(random_bytes(32));
    $expires = (new DateTime())->add(new DateInterval('PT60M'))->format('Y-m-d H:i:s');
    $ins = $pdo->prepare('INSERT INTO password_resets (user_id, reset_token, expires_at) VALUES (:uid, :t, :exp)');
    $ins->execute([':uid' => $u['id'], ':t' => $token, ':exp' => $expires]);
    
    global $config;
    $resetUrl = rtrim($config['base_url'] ?? '', '/') . '/password_reset_form.php?token=' . urlencode($token);
    $subject = 'Password reset for your tools.veerl.es account';
    $message = "Use the following link to set your password (expires in 1 hour):\n\n" . $resetUrl . "\n\nIf you did not request this, ignore this email.";
    $sent = auth_smtp_send($email, $subject, $message, $config['mail_from'] ?? 'no-reply@veerl.es');
    if ($sent) {
        return true;
    }
    return $resetUrl;
}

function auth_verify_reset_token($token) {
    $pdo = jwt_db();
    $stmt = $pdo->prepare('SELECT pr.*, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.reset_token = :t AND pr.expires_at > NOW() AND pr.used_at IS NULL LIMIT 1');
    $stmt->execute([':t' => $token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function auth_complete_password_reset($token, $newPassword) {
    $row = auth_verify_reset_token($token);
    if (!$row) return false;
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo = jwt_db();
    $u = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
    $u->execute([':h' => $hash, ':id' => $row['user_id']]);
    $mark = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
    $mark->execute([':id' => $row['id']]);
    return true;
}
