<?php
require __DIR__ . '/../include/auth_include.php';
auth_init();
auth_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; exit; }
$csrf = $_POST['csrf_token'] ?? '';
if (!auth_verify_csrf($csrf)) { echo 'Invalid CSRF'; exit; }

$uid = intval($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';
if (!$uid || !in_array($action, ['disable','enable'])) { header('Location: index.php'); exit; }

$pdo = auth_db();
if ($action === 'disable') {
    // Prevent disabling the last active Admin
    $chk = $pdo->prepare("SELECT COUNT(*) as c FROM users WHERE role = 'Admin' AND is_active = 1");
    $chk->execute();
    $row = $chk->fetch(PDO::FETCH_ASSOC);
    $countAdmins = intval($row['c'] ?? 0);
    // check if the target user is an admin
    $t = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
    $t->execute([':id' => $uid]);
    $tr = $t->fetch(PDO::FETCH_ASSOC);
    $isTargetAdmin = $tr && isset($tr['role']) && strcasecmp($tr['role'], 'admin') === 0;
    if ($isTargetAdmin && $countAdmins <= 1) {
        // cannot disable last admin
        echo 'Action blocked: cannot disable the last active Admin.';
        exit;
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
