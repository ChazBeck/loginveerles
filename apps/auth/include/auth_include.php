<?php
// Minimal auth include implementing server-side sessions stored in DB.
// Usage: put a `config.php` file in the same folder returning config array.

// Load environment variables from .env file if present
function load_env($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!array_key_exists($key, $_ENV) && !getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

load_env(__DIR__ . '/../.env');

if (file_exists(__DIR__ . '/../config.php')) {
    $config = require __DIR__ . '/../config.php';
} else {
    $config = require __DIR__ . '/../config.sample.php';
}

// Load Composer autoloader when available (for PHPMailer, etc.)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Global exception handler
set_exception_handler(function($exception) {
    auth_log_error('Uncaught exception', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ]);
    global $config;
    if (($config['environment'] ?? 'development') === 'production') {
        // In production, show generic error page
        if (file_exists(__DIR__ . '/../errors/500.php')) {
            http_response_code(500);
            include __DIR__ . '/../errors/500.php';
            exit;
        } else {
            http_response_code(500);
            die('An error occurred. Please try again later.');
        }
    } else {
        // In development, show detailed error
        http_response_code(500);
        echo '<h1>Error</h1>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($exception->getFile()) . '</p>';
        echo '<p><strong>Line:</strong> ' . $exception->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
    }
});

// Global error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    auth_log_error('PHP Error', [
        'errno' => $errno,
        'errstr' => $errstr,
        'errfile' => $errfile,
        'errline' => $errline
    ]);
    return true;
}, E_ALL);

// Set security headers
function auth_set_security_headers() {
    global $config;
    $is_production = ($config['environment'] ?? 'development') === 'production';
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; frame-ancestors 'none'");
    
    // Prevent clickjacking
    header("X-Frame-Options: DENY");
    
    // Prevent MIME type sniffing
    header("X-Content-Type-Options: nosniff");
    
    // Enable XSS protection
    header("X-XSS-Protection: 1; mode=block");
    
    // Referrer policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // HSTS - only in production with HTTPS
    if ($is_production && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    }
    
    // Permissions policy
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

auth_set_security_headers();

// We use PHP session for CSRF tokens and short-lived server state
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = null;
$currentUser = null;
$currentSession = null;

/**
 * Get the base application path from config URL
 * Extracts the path portion and removes /auth suffix for redirects
 * 
 * @return string The base app path (e.g., /loginveerles/apps)
 */
function auth_get_app_base_path() {
    global $config;
    // Extract path from base_url
    $url = $config['base_url'] ?? '';
    $parsed = parse_url($url);
    $path = $parsed['path'] ?? '/loginveerles/apps/auth';
    // Remove /auth from the end to get app base
    return rtrim(str_replace('/auth', '', $path), '/');
}

/**
 * Validate password against security policy
 * Enforces minimum length, uppercase, lowercase, numbers, and special characters
 * 
 * @param string $password The password to validate
 * @return array [bool success, string|null error_message]
 */
function auth_validate_password($password) {
    $errors = [];
    $min_length = 8;
    
    if (strlen($password) < $min_length) {
        $errors[] = "Password must be at least {$min_length} characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return empty($errors) ? [true, null] : [false, implode('. ', $errors)];
}

/**
 * Write a log entry to daily log file
 * 
 * @param string $level Log level (SECURITY, ERROR, INFO, etc.)
 * @param string $message Log message
 * @param array $context Additional context data
 */
function auth_log($level, $message, $context = []) {
    global $config;
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    $log_file = $log_dir . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $context_str = !empty($context) ? json_encode($context) : '';
    $log_entry = "[$timestamp] [$level] [$ip] $message $context_str\n";
    @error_log($log_entry, 3, $log_file);
}

/**
 * Log a security-related event
 * 
 * @param string $message Security event description
 * @param array $context Additional context data
 */
function auth_log_security($message, $context = []) {
    auth_log('SECURITY', $message, $context);
}

/**
 * Log an error and also send to PHP error log
 * 
 * @param string $message Error message
 * @param array $context Additional context data
 */
function auth_log_error($message, $context = []) {
    auth_log('ERROR', $message, $context);
    // Also log to PHP error log for critical errors
    error_log("Auth Error: $message " . json_encode($context));
}

/**
 * Handle an error with logging and user-friendly message
 * 
 * @param string $user_message Message shown to user
 * @param string|null $log_message Message logged (if different from user message)
 * @param array $context Additional context for logging
 * @return array [false, user_message]
 */
function auth_handle_error($user_message = 'An error occurred. Please try again.', $log_message = null, $context = []) {
    if ($log_message) {
        auth_log_error($log_message, $context);
    }
    return [false, $user_message];
}

/**
 * Get or generate CSRF token for current session
 * 
 * @return string CSRF token
 */
function auth_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function auth_verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Mail sending helper: prefers PHPMailer with SMTP; falls back to mail().
function auth_smtp_send($to, $subject, $body, $from) {
    global $config;
    $smtp = $config['smtp'] ?? [];

    // Use PHPMailer when available and SMTP host is configured
    if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') && !empty($smtp['host'])) {
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->Port = $smtp['port'] ?? 587;
            $secure = strtolower($smtp['secure'] ?? 'tls');
            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
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
        } catch (Exception $e) {
            return false;
        }
    }

    // Fallback to PHP mail() if PHPMailer/SMTP is unavailable
    $headers = 'From: ' . $from . "\r\n" . 'Content-Type: text/plain; charset=UTF-8\r\n';
    return mail($to, $subject, $body, $headers);
}

function auth_db() {
    global $pdo, $config;
    if ($pdo) return $pdo;
    try {
        $db = $config['db'];
        $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        return $pdo;
    } catch (PDOException $e) {
        auth_log_error('Database connection failed', ['error' => $e->getMessage()]);
        // In production, don't expose database details
        global $config;
        if (($config['environment'] ?? 'development') === 'production') {
            die('Service temporarily unavailable. Please try again later.');
        } else {
            die('Database connection error: ' . $e->getMessage());
        }
    }
}

function auth_cookie_params() {
    global $config;
    return [
        'name' => $config['cookie_name'],
        'secure' => (bool)$config['cookie_secure'],
        'samesite' => $config['cookie_samesite'],
        'path' => $config['cookie_path'],
    ];
}

function auth_init() {
    global $currentUser, $currentSession;
    try {
        $cookie = auth_cookie_params();
        if (isset($_COOKIE[$cookie['name']]) && strlen($_COOKIE[$cookie['name']]) === 64) {
            $token = $_COOKIE[$cookie['name']];
            $pdo = auth_db();
            $stmt = $pdo->prepare('SELECT s.*, u.id as uid, u.email, u.first_name, u.last_name, u.role FROM sessions s JOIN users u ON s.user_id = u.id WHERE s.session_token = :t AND s.expires_at > NOW()');
            $stmt->execute([':t' => $token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $currentSession = $row;
                $currentUser = [
                    'id' => (int)$row['uid'],
                    'email' => $row['email'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'role' => $row['role'],
                ];
                // update last_activity
                $u = auth_db()->prepare('UPDATE sessions SET last_activity = NOW() WHERE id = :id');
                $u->execute([':id' => $row['id']]);
                return true;
            }
        }
    } catch (PDOException $e) {
        auth_log_error('Session initialization failed', ['error' => $e->getMessage()]);
        // Clear invalid cookie
        auth_clear_cookie();
    }
    return false;
}

function auth_get_user() {
    global $currentUser;
    return $currentUser;
}

function auth_require_login($redirect = true) {
    if (!auth_get_user()) {
        if ($redirect) {
            $return = urlencode($_SERVER['REQUEST_URI']);
            header('Location: ../auth/login.php?return_to=' . $return);
            exit;
        }
        return false;
    }
    return true;
}

function auth_require_admin() {
    $user = auth_get_user();
    if (!$user) {
        auth_log_security('Unauthorized admin access attempt - not logged in', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        header('Location: ../login.php');
        exit;
    }
    if (strcasecmp($user['role'], 'Admin') !== 0) {
        auth_log_security('Unauthorized admin access attempt', ['user_id' => $user['id'], 'email' => $user['email']]);
        http_response_code(403);
        if (file_exists(__DIR__ . '/../errors/403.php')) {
            include __DIR__ . '/../errors/403.php';
        } else {
            die('Access denied. Admin privileges required.');
        }
        exit;
    }
    return true;
}

function auth_set_cookie($token, $remember = false) {
    global $config;
    $cookie = auth_cookie_params();
    $expiry = $remember ? time() + (60 * 60 * 24 * $config['remember_days']) : 0; // 0 = session cookie
    // Use __Host- prefix if cookie is set from the origin with path=/ and no Domain attribute
    $name = $cookie['name'];
    if (strpos($name, '__Host-') !== 0) {
        $name = $name;
    }
    setcookie($name, $token, $expiry, $cookie['path'], '', $cookie['secure'], true);
}

function auth_clear_cookie() {
    $cookie = auth_cookie_params();
    setcookie($cookie['name'], '', time() - 3600, $cookie['path'], '', false, true);
    unset($_COOKIE[$cookie['name']]);
}

function auth_generate_token() {
    return bin2hex(random_bytes(32));
}

function auth_login($email, $password, $remember = false) {
    try {
        $pdo = auth_db();
        // Rate limiting: count failed attempts for this email and IP in last 15 minutes
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $rl = $pdo->prepare('SELECT COUNT(*) as c FROM login_attempts WHERE (user_email = :e OR ip = :ip) AND success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE)');
        $rl->execute([':e' => $email, ':ip' => $ip]);
        $rowrl = $rl->fetch(PDO::FETCH_ASSOC);
        if ($rowrl && $rowrl['c'] > 10) {
            auth_log_security('Rate limit exceeded', ['email' => $email, 'ip' => $ip]);
            return [false, 'Too many failed attempts. Try again later.'];
        }
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :e LIMIT 1');
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            auth_log_security('Login attempt - user not found', ['email' => $email]);
            return [false, 'Invalid credentials'];
        }
        if (!$user['password_hash']) return [false, 'No password set for user. Request password reset.'];
        if (!password_verify($password, $user['password_hash'])) {
            // record attempt
            $ins = $pdo->prepare('INSERT INTO login_attempts (user_email, user_id, success, ip) VALUES (:e, :id, 0, :ip)');
            $ins->execute([':e'=>$email, ':id'=>$user['id'], ':ip'=>$_SERVER['REMOTE_ADDR'] ?? null]);
            auth_log_security('Failed login attempt', ['email' => $email, 'user_id' => $user['id']]);
            return [false, 'Invalid credentials'];
        }
        // record successful login attempt
        $insOk = $pdo->prepare('INSERT INTO login_attempts (user_email, user_id, success, ip) VALUES (:e, :id, 1, :ip)');
        $insOk->execute([':e'=>$email, ':id'=>$user['id'], ':ip'=>$_SERVER['REMOTE_ADDR'] ?? null]);
        auth_log_security('Successful login', ['email' => $email, 'user_id' => $user['id']]);
        // create session
        $token = auth_generate_token();
        $expires = (new DateTime())->add(new DateInterval('PT' . (intval($GLOBALS['config']['session_lifetime_minutes']) * 60) . 'S'))->format('Y-m-d H:i:s');
        if ($remember) {
            $expires = (new DateTime())->add(new DateInterval('P' . intval($GLOBALS['config']['remember_days']) . 'D'))->format('Y-m-d H:i:s');
        }
        $ins = $pdo->prepare('INSERT INTO sessions (session_token, user_id, ip, user_agent_hash, expires_at, remember_flag) VALUES (:t, :uid, :ip, :ua, :exp, :rem)');
        $ins->execute([
            ':t' => $token,
            ':uid' => $user['id'],
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''),
            ':exp' => $expires,
            ':rem' => $remember ? 1 : 0,
        ]);
        auth_set_cookie($token, $remember);
        // Regenerate PHP session ID to prevent session fixation attacks
        session_regenerate_id(true);
        // update last_login
        $u = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
        $u->execute([':id' => $user['id']]);
        return [true, null];
    } catch (PDOException $e) {
        auth_log_error('Login process failed', ['error' => $e->getMessage(), 'email' => $email]);
        return auth_handle_error('Login failed. Please try again.');
    }
}

function auth_logout() {
    global $currentSession;
    if ($currentSession) {
        $pdo = auth_db();
        $del = $pdo->prepare('DELETE FROM sessions WHERE id = :id');
        $del->execute([':id' => $currentSession['id']]);
    }
    auth_clear_cookie();
    $currentSession = null;
    $GLOBALS['currentUser'] = null;
}

function auth_request_password_reset($email) {
    $pdo = auth_db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
    $stmt->execute([':e' => $email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) return false;
    $token = auth_generate_token();
    $expires = (new DateTime())->add(new DateInterval('PT60M'))->format('Y-m-d H:i:s');
    $ins = $pdo->prepare('INSERT INTO password_resets (user_id, reset_token, expires_at) VALUES (:uid, :t, :exp)');
    $ins->execute([':uid' => $u['id'], ':t' => $token, ':exp' => $expires]);
    // build reset URL and attempt to send via SMTP helper (falls back to mail())
    $resetUrl = rtrim($GLOBALS['config']['base_url'], '/') . '/password_reset_form.php?token=' . urlencode($token);
    $subject = 'Password reset for your tools.veerl.es account';
    $message = "Use the following link to set your password (expires in 1 hour):\n\n" . $resetUrl . "\n\nIf you did not request this, ignore this email.";
    $sent = auth_smtp_send($email, $subject, $message, $GLOBALS['config']['mail_from']);
    if ($sent) {
        return true;
    }
    // If sending failed (likely on localhost), return the reset URL so caller can display it for provisioning.
    return $resetUrl;
}

function auth_verify_reset_token($token) {
    $pdo = auth_db();
    $stmt = $pdo->prepare('SELECT pr.*, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.reset_token = :t AND pr.expires_at > NOW() AND pr.used_at IS NULL LIMIT 1');
    $stmt->execute([':t' => $token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function auth_complete_password_reset($token, $newPassword) {
    $row = auth_verify_reset_token($token);
    if (!$row) return false;
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo = auth_db();
    $u = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
    $u->execute([':h' => $hash, ':id' => $row['user_id']]);
    $mark = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
    $mark->execute([':id' => $row['id']]);
    return true;
}

// Admin helpers
function auth_is_admin() {
    $u = auth_get_user();
    return $u && isset($u['role']) && strcasecmp($u['role'], 'admin') === 0;
}
