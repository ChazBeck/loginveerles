<?php
/**
 * Authentication System Test Script
 * Tests the refactored JWT authentication system
 * 
 * Usage: php test_auth_system.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Authentication System Test Suite ===\n\n";

$tests_passed = 0;
$tests_failed = 0;

function test($name, $callback) {
    global $tests_passed, $tests_failed;
    echo "Testing: $name ... ";
    try {
        $result = $callback();
        if ($result === true) {
            echo "✓ PASS\n";
            $tests_passed++;
        } else {
            echo "✗ FAIL: $result\n";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        $tests_failed++;
    }
}

// Change to script directory
chdir(__DIR__);

// Test 1: Configuration loading
test("Configuration loading", function() {
    if (!file_exists(__DIR__ . '/../config.php')) {
        return "config.php not found";
    }
    $config = require __DIR__ . '/../config.php';
    if (!is_array($config)) {
        return "config.php doesn't return an array";
    }
    if (!isset($config['db'], $config['jwt_cookie_name'], $config['jwt_lifetime_minutes'])) {
        return "Missing required JWT configuration keys";
    }
    if (isset($config['cookie_name']) || isset($config['session_lifetime_minutes'])) {
        return "Old session config still present (should be removed)";
    }
    return true;
});

// Test 2: Common functions file exists
test("Common functions file exists", function() {
    if (!file_exists(__DIR__ . '/../include/common_functions.php')) {
        return "common_functions.php not found";
    }
    require_once __DIR__ . '/../include/common_functions.php';
    if (!function_exists('auth_db')) {
        return "auth_db() function not found";
    }
    if (!function_exists('auth_csrf_token')) {
        return "auth_csrf_token() function not found";
    }
    if (!function_exists('auth_smtp_send')) {
        return "auth_smtp_send() function not found";
    }
    if (!function_exists('auth_request_password_reset')) {
        return "auth_request_password_reset() function not found";
    }
    return true;
});

// Test 3: JWT include file exists and loads
test("JWT include file exists", function() {
    if (!file_exists(__DIR__ . '/../include/jwt_include.php')) {
        return "jwt_include.php not found";
    }
    require_once __DIR__ . '/../include/jwt_include.php';
    if (!function_exists('jwt_init')) {
        return "jwt_init() function not found";
    }
    if (!function_exists('jwt_get_user')) {
        return "jwt_get_user() function not found";
    }
    if (!function_exists('jwt_require_login')) {
        return "jwt_require_login() function not found";
    }
    return true;
});

// Test 4: Old auth_include.php should NOT exist
test("Old auth_include.php removed", function() {
    if (file_exists(__DIR__ . '/../include/auth_include.php')) {
        return "auth_include.php still exists (should be deleted)";
    }
    return true;
});

// Test 5: JWT keys exist
test("JWT keys exist", function() {
    if (!file_exists(__DIR__ . '/../jwt_public.pem')) {
        return "jwt_public.pem not found";
    }
    if (!file_exists(__DIR__ . '/../jwt_private.pem')) {
        return "jwt_private.pem not found";
    }
    $publicKey = file_get_contents(__DIR__ . '/../jwt_public.pem');
    $privateKey = file_get_contents(__DIR__ . '/../jwt_private.pem');
    if (strpos($publicKey, 'BEGIN PUBLIC KEY') === false) {
        return "jwt_public.pem doesn't look like a public key";
    }
    if (strpos($privateKey, 'BEGIN RSA PRIVATE KEY') === false && strpos($privateKey, 'BEGIN PRIVATE KEY') === false) {
        return "jwt_private.pem doesn't look like a private key";
    }
    return true;
});

// Test 6: Database connection
test("Database connection", function() {
    try {
        $pdo = auth_db();
        if (!$pdo instanceof PDO) {
            return "auth_db() didn't return PDO instance";
        }
        // Test query
        $stmt = $pdo->query('SELECT 1');
        if (!$stmt) {
            return "Failed to execute test query";
        }
        return true;
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
});

// Test 7: Required database tables exist
test("Required database tables exist", function() {
    $pdo = auth_db();
    $tables = ['users', 'sessions', 'password_resets', 'login_attempts'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            return "Table '$table' not found";
        }
    }
    return true;
});

// Test 8: Sessions table has revoked_at column (for JWT revocation)
test("Sessions table has revoked_at column", function() {
    $pdo = auth_db();
    $stmt = $pdo->query("SHOW COLUMNS FROM sessions LIKE 'revoked_at'");
    if ($stmt->rowCount() === 0) {
        return "sessions table missing revoked_at column (run jwt_revocation_migration.sql)";
    }
    return true;
});

// Test 9: CSRF token generation
test("CSRF token generation", function() {
    $token1 = auth_csrf_token();
    if (empty($token1)) {
        return "CSRF token is empty";
    }
    if (strlen($token1) < 16) {
        return "CSRF token too short";
    }
    // Second call should return same token
    $token2 = auth_csrf_token();
    if ($token1 !== $token2) {
        return "CSRF token not consistent";
    }
    return true;
});

// Test 10: CSRF token verification
test("CSRF token verification", function() {
    $token = auth_csrf_token();
    if (!auth_verify_csrf($token)) {
        return "Valid CSRF token rejected";
    }
    if (auth_verify_csrf('invalid_token')) {
        return "Invalid CSRF token accepted";
    }
    return true;
});

// Test 11: JWT encoding/decoding
test("JWT encoding and decoding", function() {
    $privateKey = file_get_contents(__DIR__ . '/../jwt_private.pem');
    $publicKey = file_get_contents(__DIR__ . '/../jwt_public.pem');
    
    $payload = [
        'iss' => 'test',
        'iat' => time(),
        'exp' => time() + 300,
        'sub' => '123',
        'email' => 'test@example.com',
        'role' => 'User',
    ];
    
    $jwt = Firebase\JWT\JWT::encode($payload, $privateKey, 'RS256');
    if (empty($jwt)) {
        return "Failed to encode JWT";
    }
    
    $decoded = Firebase\JWT\JWT::decode($jwt, new Firebase\JWT\Key($publicKey, 'RS256'));
    if ($decoded->sub !== '123') {
        return "JWT decode failed - wrong subject";
    }
    if ($decoded->email !== 'test@example.com') {
        return "JWT decode failed - wrong email";
    }
    
    return true;
});

// Test 12: User lookup
test("Test user exists in database", function() {
    $pdo = auth_db();
    $stmt = $pdo->prepare('SELECT COUNT(*) as c FROM users WHERE email = :e');
    $stmt->execute([':e' => 'charlie@veerless.com']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ((int)$row['c'] === 0) {
        return "Test user charlie@veerless.com not found";
    }
    return true;
});

// Test 13: Login page doesn't use old auth_include.php
test("Login page uses new system", function() {
    $login = file_get_contents(__DIR__ . '/../login.php');
    if (strpos($login, "require __DIR__ . '/include/auth_include.php'") !== false) {
        return "login.php still requires auth_include.php";
    }
    if (strpos($login, "require __DIR__ . '/include/common_functions.php'") === false) {
        return "login.php doesn't require common_functions.php";
    }
    if (strpos($login, 'auth_login(') !== false) {
        return "login.php still uses auth_login() function";
    }
    return true;
});

// Test 14: Password hashing works
test("Password hashing and verification", function() {
    $password = 'test123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if (!password_verify($password, $hash)) {
        return "Password verification failed";
    }
    if (password_verify('wrong', $hash)) {
        return "Wrong password verified as correct";
    }
    return true;
});

// Test 15: Vendor autoloader works
test("Composer vendor autoloader works", function() {
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        return "vendor/autoload.php not found";
    }
    if (!class_exists('Firebase\JWT\JWT')) {
        return "Firebase JWT class not loaded";
    }
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return "PHPMailer class not loaded";
    }
    return true;
});

// Summary
echo "\n=== Test Results ===\n";
echo "Passed: $tests_passed\n";
echo "Failed: $tests_failed\n";
echo "Total:  " . ($tests_passed + $tests_failed) . "\n\n";

if ($tests_failed === 0) {
    echo "✓ All tests passed! Authentication system is working correctly.\n";
    exit(0);
} else {
    echo "✗ Some tests failed. Please fix the issues above.\n";
    exit(1);
}
