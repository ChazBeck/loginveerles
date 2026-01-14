<?php
// Minimal auth include implementing server-side sessions stored in DB.
// Usage: put a `config.php` file in the same folder returning config array.

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

// We use PHP session for CSRF tokens and short-lived server state
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = null;
$currentUser = null;
$currentSession = null;

// CSRF helpers
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
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    return $pdo;
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
    $pdo = auth_db();
    // Rate limiting: count failed attempts for this email and IP in last 15 minutes
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $rl = $pdo->prepare('SELECT COUNT(*) as c FROM login_attempts WHERE (user_email = :e OR ip = :ip) AND success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE)');
    $rl->execute([':e' => $email, ':ip' => $ip]);
    $rowrl = $rl->fetch(PDO::FETCH_ASSOC);
    if ($rowrl && $rowrl['c'] > 10) {
        return [false, 'Too many failed attempts. Try again later.'];
    }
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :e LIMIT 1');
    $stmt->execute([':e' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) return [false, 'Invalid credentials'];
    if (!$user['password_hash']) return [false, 'No password set for user. Request password reset.'];
    if (!password_verify($password, $user['password_hash'])) {
        // record attempt
        $ins = $pdo->prepare('INSERT INTO login_attempts (user_email, user_id, success, ip) VALUES (:e, :id, 0, :ip)');
        $ins->execute([':e'=>$email, ':id'=>$user['id'], ':ip'=>$_SERVER['REMOTE_ADDR'] ?? null]);
        return [false, 'Invalid credentials'];
    }
    // record successful login attempt
    $insOk = $pdo->prepare('INSERT INTO login_attempts (user_email, user_id, success, ip) VALUES (:e, :id, 1, :ip)');
    $insOk->execute([':e'=>$email, ':id'=>$user['id'], ':ip'=>$_SERVER['REMOTE_ADDR'] ?? null]);
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
    // update last_login
    $u = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
    $u->execute([':id' => $user['id']]);
    return [true, null];
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

function auth_require_admin() {
    auth_require_login();
    if (!auth_is_admin()) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

