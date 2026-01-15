<?php
// JWT-based SSO authentication library
// Usage: require this file, call jwt_init(), then use jwt_require_login()

// Load common utilities (database, CSRF, email, password reset)
require_once __DIR__ . '/common_functions.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$currentUser = null;

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
        
        // Check if JWT has been revoked (optional)
        if (isset($decoded->jti)) {
            $pdo = auth_db();
            $stmt = $pdo->prepare('SELECT * FROM sessions WHERE jti = :jti AND revoked_at IS NULL LIMIT 1');
            $stmt->execute([':jti' => $decoded->jti]);
            if (!$stmt->fetch()) {
                // JWT has been revoked or not found
                jwt_clear_cookie();
                return false;
            }
        }
        
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
    
    // Revoke session in database if we have the JWT ID
    if ($currentUser && isset($currentUser['jti'])) {
        try {
            $pdo = auth_db();
            $stmt = $pdo->prepare('UPDATE sessions SET revoked_at = NOW() WHERE jti = :jti');
            $stmt->execute([':jti' => $currentUser['jti']]);
        } catch (Exception $e) {
            // Continue with logout even if DB update fails
        }
    }
    
    jwt_clear_cookie();
    $currentUser = null;
}

// Compatibility aliases for apps that use auth_* function names
// These functions are now defined in common_functions.php
function auth_get_user() { return jwt_get_user(); }
function auth_is_logged_in() { return jwt_is_logged_in(); }
function auth_require_login($redirect = true) { return jwt_require_login($redirect); }
function auth_is_admin() { return jwt_is_admin(); }
function auth_require_admin() { return jwt_require_admin(); }
function auth_logout() { return jwt_logout(); }
