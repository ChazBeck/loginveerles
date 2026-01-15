<?php

// Helper function to read environment variables
if (!function_exists('env')) {
    function env($key, $default = null) {
        static $env_loaded = false;
        static $env_vars = [];
        
        if (!$env_loaded) {
            $env_file = __DIR__ . '/.env';
            if (file_exists($env_file)) {
                $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    list($key_part, $value) = explode('=', $line, 2);
                    $env_vars[trim($key_part)] = trim($value);
                }
            }
            $env_loaded = true;
        }
        
        return isset($env_vars[$key]) ? $env_vars[$key] : $default;
    }
}

// Detect environment
$is_production = env('APP_ENV') === 'production';

// Auto-detect HTTPS
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

return [
    'db' => [
        'host' => env('DB_HOST', 'localhost'),
        'name' => env('DB_NAME', 'loginveerles'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASS', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
    ],
    
    // JWT cookie settings
    'jwt_cookie_name' => env('JWT_COOKIE_NAME', 'sso_token'),
    'jwt_cookie_domain' => env('JWT_COOKIE_DOMAIN', 'localhost'),
    'jwt_lifetime_minutes' => (int)env('JWT_LIFETIME_MINUTES', 60),
    'cookie_secure' => filter_var(env('COOKIE_SECURE', $is_https), FILTER_VALIDATE_BOOLEAN),
    'cookie_samesite' => env('COOKIE_SAMESITE', 'Lax'),
    'cookie_path' => env('COOKIE_PATH', '/'),
    
    // Application base URL
    'base_url' => env('APP_BASE_URL', 'http://localhost/loginveerles'),
    
    // Email settings
    'mail_from' => env('MAIL_FROM', 'no-reply@example.com'),
    
    // SMTP settings
    'smtp' => [
        'host' => env('SMTP_HOST', ''),
        'port' => (int)env('SMTP_PORT', 587),
        'username' => env('SMTP_USERNAME', ''),
        'password' => env('SMTP_PASSWORD', ''),
        'secure' => env('SMTP_SECURE', 'tls'),
        'timeout' => (int)env('SMTP_TIMEOUT', 30),
    ],
];
