<?php
// Shared utility functions for authentication system
// Used by both JWT and legacy components

// Load configuration
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

// Global database connection
$pdo = null;

/**
 * Get database connection
 */
function auth_db() {
    global $pdo, $config;
    if ($pdo) return $pdo;
    
    // Ensure config is loaded
    if (!$config) {
        if (file_exists(__DIR__ . '/../config.php')) {
            $config = require __DIR__ . '/../config.php';
        } else {
            throw new Exception('Configuration file not found');
        }
    }
    
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    return $pdo;
}

/**
 * CSRF Token Generation
 */
function auth_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Token Verification
 */
function auth_verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Send email via SMTP (using PHPMailer) or fallback to PHP mail()
 */
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

/**
 * Request password reset - generates token and sends email
 */
function auth_request_password_reset($email) {
    $pdo = auth_db();
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

/**
 * Verify password reset token is valid and not expired
 */
function auth_verify_reset_token($token) {
    $pdo = auth_db();
    $stmt = $pdo->prepare('SELECT pr.*, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.reset_token = :t AND pr.expires_at > NOW() AND pr.used_at IS NULL LIMIT 1');
    $stmt->execute([':t' => $token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Complete password reset - update password and mark token as used
 */
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
