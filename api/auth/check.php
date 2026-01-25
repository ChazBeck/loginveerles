<?php
/**
 * SSO Check Endpoint
 * Returns authentication status and user data for shared header
 */

// Enable CORS if your apps are on different domains
// In production, replace * with your specific domain, e.g., 'https://tools.veerl.es'
$allowed_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $allowed_origin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../include/jwt_include.php';

// Initialize JWT authentication
$config = require __DIR__ . '/../../config.php';
jwt_init();

// Get current user
$user = jwt_get_user();

if ($user && jwt_is_logged_in()) {
    // User is authenticated - return user data
    $response = [
        'authenticated' => true,
        'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
        'username' => $user['email'] ?? '',
        'email' => $user['email'] ?? '',
        'first_name' => $user['first_name'] ?? '',
        'last_name' => $user['last_name'] ?? '',
        'role' => $user['role'] ?? 'User',
        'isAdmin' => jwt_is_admin(),
        // Generate avatar URL using UI Avatars service
        'avatar' => 'https://ui-avatars.com/api/?' . http_build_query([
            'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'background' => '667eea',
            'color' => 'fff',
            'size' => '128',
            'bold' => 'true'
        ])
    ];
} else {
    // User is not authenticated
    $response = [
        'authenticated' => false
    ];
}

echo json_encode($response);
