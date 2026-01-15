<?php
require __DIR__ . '/../include/jwt_include.php';
jwt_init();
jwt_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!auth_verify_csrf($csrf)) {
    echo 'Invalid CSRF'; exit;
}

$email = trim($_POST['email'] ?? '');
$first = trim($_POST['first_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$role = $_POST['role'] ?? 'User';
$password = trim($_POST['password'] ?? '');
$sendReset = isset($_POST['send_reset']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo 'Invalid email'; exit;
}

$pdo = auth_db();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
$stmt->execute([':e' => $email]);
if ($stmt->fetch()) {
    header('Location: index.php'); exit;
}

// Hash password if provided, otherwise NULL
$passwordHash = null;
if ($password !== '') {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
}

$ins = $pdo->prepare('INSERT INTO users (email, password_hash, first_name, last_name, role, is_active, created_at) VALUES (:e, :ph, :fn, :ln, :r, 1, NOW())');
$ins->execute([':e'=>$email, ':ph'=>$passwordHash, ':fn'=>$first, ':ln'=>$last, ':r'=>$role]);

if ($sendReset && $password === '') {
    $res = auth_request_password_reset($email);
    // If auth_request_password_reset returns URL on failure, optionally log or show it.
}

header('Location: index.php');
exit;
