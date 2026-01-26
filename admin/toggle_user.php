<?php
require __DIR__ . '/../include/jwt_include.php';
jwt_init();
jwt_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; exit; }
$csrf = $_POST['csrf_token'] ?? '';
if (!auth_verify_csrf($csrf)) { echo 'Invalid CSRF'; exit; }

$uid = intval($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';
if (!$uid || !in_array($action, ['disable','enable'])) { header('Location: index.php'); exit; }

$pdo = auth_db();

if ($action === 'disable') {
    // Check if we can safely disable this user
    $userStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $uid]);
    $targetUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($targetUser) {
        $check = auth_can_modify_admin($uid, $targetUser['role'], 0);
        if (!$check['allowed']) {
            echo 'Action blocked: ' . htmlspecialchars($check['reason']);
            exit;
        }
    }
    
    $upd = $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = :id');
    $upd->execute([':id' => $uid]);
    
    // Remove sessions for that user
    $del = $pdo->prepare('DELETE FROM sessions WHERE user_id = :id');
    $del->execute([':id' => $uid]);
} else {
    $upd = $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = :id');
    $upd->execute([':id' => $uid]);
}

header('Location: index.php');
exit;
