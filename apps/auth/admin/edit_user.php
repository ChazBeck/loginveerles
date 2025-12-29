<?php
require __DIR__ . '/../include/auth_include.php';
auth_init();
auth_require_admin();

$pdo = auth_db();

// GET: show form; POST: save changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!auth_verify_csrf($csrf)) { echo 'Invalid CSRF'; exit; }
    $id = intval($_POST['user_id'] ?? 0);
    if (!$id) { header('Location: index.php'); exit; }
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $role = $_POST['role'] ?? 'User';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    // Prevent demoting or disabling the last active Admin
    $cur = $pdo->prepare('SELECT role, is_active FROM users WHERE id = :id LIMIT 1');
    $cur->execute([':id' => $id]);
    $current = $cur->fetch(PDO::FETCH_ASSOC);
    if ($current) {
        $wasAdmin = isset($current['role']) && strcasecmp($current['role'], 'admin') === 0;
        $wasActive = intval($current['is_active']) === 1;
        $willBeAdmin = strcasecmp($role, 'admin') === 0;
        $willBeActive = intval($is_active) === 1;
        if ($wasAdmin && (!$willBeAdmin || !$willBeActive)) {
            $chk = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role = 'Admin' AND is_active = 1");
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            $countAdmins = intval($row['c'] ?? 0);
            if ($countAdmins <= 1) {
                echo 'Action blocked: cannot remove admin privileges or deactivate the last active Admin.';
                exit;
            }
        }
    }

    $upd = $pdo->prepare('UPDATE users SET first_name = :fn, last_name = :ln, role = :role, is_active = :active WHERE id = :id');
    $upd->execute([':fn' => $first, ':ln' => $last, ':role' => $role, ':active' => $is_active, ':id' => $id]);
    if (!$is_active) {
        $del = $pdo->prepare('DELETE FROM sessions WHERE user_id = :id');
        $del->execute([':id' => $id]);
    }
    // Optionally send reset link
    if (isset($_POST['send_reset']) && $_POST['send_reset'] === '1') {
        // find email
        $s = $pdo->prepare('SELECT email FROM users WHERE id = :id LIMIT 1');
        $s->execute([':id' => $id]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if ($row) auth_request_password_reset($row['email']);
    }
    header('Location: index.php'); exit;
}

$id = intval($_GET['user_id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }
$stmt = $pdo->prepare('SELECT id, email, first_name, last_name, role, is_active FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: index.php'); exit; }

?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit User - <?=htmlspecialchars($user['email'])?></title>
  <link rel="stylesheet" href="../../assets/styles.css">
  <style>
    .admin-container {
      max-width: 800px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .admin-section {
      background: #FFFFFF;
      border: 1px solid #CAC1A9;
      border-radius: 6px;
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 2px 4px rgba(4, 53, 70, 0.1);
    }
    .admin-section h2 {
      margin-top: 0;
      color: #043546;
      font-family: 'Archivo', sans-serif;
      border-bottom: 2px solid #E58325;
      padding-bottom: 10px;
    }
    .admin-form label {
      display: block;
      margin: 15px 0 5px;
      color: #043546;
      font-family: 'Archivo', sans-serif;
      font-weight: 600;
    }
    .admin-form input[type="text"],
    .admin-form select {
      width: 100%;
      max-width: 400px;
      padding: 10px;
      border: 1px solid #CAC1A9;
      border-radius: 6px;
      font-family: 'Poppins', sans-serif;
    }
    .admin-form button {
      margin-top: 20px;
      padding: 12px 30px;
      background-color: #E58325;
      color: #FFFFFF;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-family: 'Archivo', sans-serif;
      font-weight: 600;
      text-transform: uppercase;
    }
    .admin-form button:hover {
      background-color: #00434F;
    }
    .back-link {
      display: inline-block;
      margin-top: 20px;
      color: #E58325;
      text-decoration: none;
      font-weight: 600;
    }
    .back-link:hover {
      color: #00434F;
    }
  </style>
</head>
<body>
<div class="site-header">
  <div class="container">
    <a href="../../index.php"><img class="site-logo" src="../../assets/logo.png" alt="Site logo"></a>
    <nav>
      <ul>
        <li><a href="../../index.php">HOME</a></li>
        <li><a href="../admin/">ADMIN</a></li>
        <li><a href="../logout.php">LOG OUT</a></li>
      </ul>
    </nav>
  </div>
</div>
<div class="admin-container">
  <div class="admin-section">
    <h2>✏️ Edit User: <?=htmlspecialchars($user['email'])?></h2>
    <form method="post" class="admin-form">
      <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
      <input type="hidden" name="user_id" value="<?=intval($user['id'])?>">
      <label>First Name
        <input type="text" name="first_name" value="<?=htmlspecialchars($user['first_name'])?>">
      </label>
      <label>Last Name
        <input type="text" name="last_name" value="<?=htmlspecialchars($user['last_name'])?>">
      </label>
      <label>Role
        <select name="role">
          <option value="User" <?= $user['role']==='User' ? 'selected' : '' ?>>User</option>
          <option value="Admin" <?= $user['role']==='Admin' ? 'selected' : '' ?>>Admin</option>
        </select>
      </label>
      <label style="font-weight: normal;">
        <input type="checkbox" name="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?> style="width: auto; margin-right: 8px;">
        Active
      </label>
      <label style="font-weight: normal;">
        <input type="checkbox" name="send_reset" value="1" style="width: auto; margin-right: 8px;">
        Send password reset email
      </label>
      <button type="submit">Save Changes</button>
    </form>
    <a href="index.php" class="back-link">← Back to User Management</a>
  </div>
</div>
</body>
</html>
