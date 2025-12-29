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
    'cookie_name' => 'appsess',
    // For local XAMPP set secure=false; on GreenGeeks set true and ensure HTTPS
    'cookie_secure' => false,
    'cookie_samesite' => 'Lax', // Lax is a good default
    'cookie_path' => '/',
    'session_lifetime_minutes' => 30,
    'remember_days' => 30,
    // Application base URL (no trailing slash)
    'base_url' => 'http://tools.veerl.es/apps/auth',
    // Email from address for password resets
    'mail_from' => 'no-reply@veerl.es',
    // Optional SMTP settings. Uses PHPMailer when host is set; otherwise falls back to PHP mail().
    'smtp' => [
        'host' => '',
        'port' => 587,
        'username' => '',
        'password' => '',
        'secure' => 'tls', // 'tls' or 'ssl' or ''
        'timeout' => 30,
    ],
];
