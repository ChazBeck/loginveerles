<?php
// Copy this to config.php and set values for your environment
return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'loginveerles',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    // Cookie and session settings
    'cookie_name' => 'appsess', // Legacy session cookie (keep for backward compatibility)
    'jwt_cookie_name' => 'sso_token', // New JWT cookie for SSO
    'jwt_cookie_domain' => 'localhost', // Domain for SSO cookie (change to tools.veerl.es in production)
    // For local XAMPP set secure=false; on GreenGeeks set true and ensure HTTPS
    'cookie_secure' => false,
    'cookie_samesite' => 'Lax', // Lax is a good default
    'cookie_path' => '/',
    'session_lifetime_minutes' => 30,
    'jwt_lifetime_minutes' => 60, // JWT token lifetime (60 minutes)
    'remember_days' => 30,
    // Application base URL (no trailing slash)
    // For local testing use localhost; remember to change back before production.
    'base_url' => 'http://localhost/loginveerles/apps/auth',
    // Email from address for password resets
    'mail_from' => 'no-reply@veerl.es',
    // Optional SMTP settings. Uses PHPMailer when host is set; otherwise falls back to PHP mail().
    'smtp' => [
        'host' => 'mail.veerl.es',
        'port' => 465,
        'username' => 'no-reply@veerl.es',
        'password' => 'xhnk%mVu+]{(2Oby',
        'secure' => 'ssl', // 'tls' or 'ssl' or ''
        'timeout' => 30,
    ],
];
