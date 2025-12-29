<?php
// Configuration file - loads from environment variables with fallback defaults
// Copy .env.example to .env and configure your environment-specific values

function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $_ENV[$key] ?? $default;
    }
    // Convert string booleans
    if (strtolower($value) === 'true') return true;
    if (strtolower($value) === 'false') return false;
    if (strtolower($value) === 'null') return null;
    return $value;
}

// Detect environment
$app_env = env('APP_ENV', 'development');
$is_production = ($app_env === 'production');
$is_development = ($app_env === 'development');

// Auto-detect HTTPS
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

return [
    'environment' => $app_env,
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'name' => env('DB_NAME', 'loginveerles'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASS', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
    ],
    // Cookie and session settings
    'cookie_name' => env('COOKIE_NAME', 'appsess'),
    // Force secure cookies in production or when HTTPS is detected
    'cookie_secure' => $is_production || $is_https ? true : env('COOKIE_SECURE', false),
    'cookie_samesite' => env('COOKIE_SAMESITE', 'Lax'),
    'cookie_path' => env('COOKIE_PATH', '/'),
    'session_lifetime_minutes' => env('SESSION_LIFETIME_MINUTES', 30),
    'remember_days' => env('REMEMBER_DAYS', 30),
    // Application base URL (no trailing slash)
    'base_url' => env('APP_BASE_URL', 'http://localhost/loginveerles/apps/auth'),
    // Email from address for password resets
    'mail_from' => env('MAIL_FROM', 'no-reply@example.com'),
    // Optional SMTP settings. Uses PHPMailer when host is set; otherwise falls back to PHP mail().
    'smtp' => [
        'host' => env('SMTP_HOST', ''),
        'port' => env('SMTP_PORT', 465),
        'username' => env('SMTP_USERNAME', ''),
        'password' => env('SMTP_PASSWORD', ''),
        'secure' => env('SMTP_SECURE', 'ssl'), // 'tls' or 'ssl' or ''
        'timeout' => env('SMTP_TIMEOUT', 30),
    ],
];

