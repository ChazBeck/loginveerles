<?php
require __DIR__ . '/../include/jwt_include.php';
jwt_init();
jwt_require_admin();

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
    
    // Check if we can safely modify this user using centralized function
    $check = auth_can_modify_admin($id, $role, $is_active);
    if (!$check['allowed']) {
        echo 'Action blocked: ' . htmlspecialchars($check['reason']);
        exit;
    }

    // Update password if provided
    $newPassword = trim($_POST['new_password'] ?? '');
    if ($newPassword !== '') {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE users SET first_name = :fn, last_name = :ln, role = :role, is_active = :active, password_hash = :ph WHERE id = :id');
        $upd->execute([':fn' => $first, ':ln' => $last, ':role' => $role, ':active' => $is_active, ':ph' => $passwordHash, ':id' => $id]);
    } else {
        $upd = $pdo->prepare('UPDATE users SET first_name = :fn, last_name = :ln, role = :role, is_active = :active WHERE id = :id');
        $upd->execute([':fn' => $first, ':ln' => $last, ':role' => $role, ':active' => $is_active, ':id' => $id]);
    }
    
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
    header('Location: index.php?success=User updated successfully'); exit;
}

$id = intval($_GET['user_id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }
$stmt = $pdo->prepare('SELECT id, email, first_name, last_name, role, is_active FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: index.php'); exit; }

?>
<header class="header">
  <a href="../index.php" class="logo">Apps Auth</a>
  <nav>
    <?php if (jwt_is_admin()): ?>
      <a href="index.php">ADMIN</a>
    <?php endif; ?>
    <a href="../logout.php">LOG OUT</a>
  </nav>
</header>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit User - Admin</title>
  <style>
    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
      background: url('../assets/images/v2osk-214954-unsplash.jpg') center/cover fixed;
      min-height: 100vh;
    }
    .header {
      background: rgba(4, 53, 70, 0.95);
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .header .logo {
      color: white;
      font-size: 1.5rem;
      font-weight: bold;
      text-decoration: none;
    }
    .header nav {
      display: flex;
      gap: 20px;
      align-items: center;
    }
    .header nav a {
      color: white;
      text-decoration: none;
      padding: 8px 16px;
      border-radius: 4px;
      transition: background 0.2s;
    }
    .header nav a:hover {
      background: rgba(229, 131, 37, 0.2);
    }
    .header nav a.active {
      background: #E58325;
    }
    .admin-container {
      max-width: 800px;
      margin: 40px auto;
      padding: 20px;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .admin-container h1 {
      color: #043546;
      margin-bottom: 30px;
      font-size: 1.8rem;
    }
    .edit-form {
      background: #f8f9fa;
      padding: 30px;
      border-radius: 4px;
    }
    .edit-form label {
      display: block;
      margin-bottom: 20px;
      color: #043546;
      font-weight: 500;
    }
    .edit-form input[type="text"],
    .edit-form select {
      width: 100%;
      max-width: 500px;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
      margin-top: 5px;
    }
    .edit-form input[type="text"]:focus,
    .edit-form select:focus {
      outline: none;
      border-color: #E58325;
    }
    .checkbox-label {
      display: flex;
      align-items: center;
    }
    .checkbox-label input[type="checkbox"] {
      width: auto;
      margin-right: 8px;
      cursor: pointer;
    }
    .edit-form button {
      background: #E58325;
      color: white;
      border: none;
      padding: 12px 30px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      margin-top: 20px;
    }
    .edit-form button:hover {
      background: #d67520;
    }
    .back-link {
      margin-top: 20px;
      text-align: center;
    }
    .back-link a {
      color: #E58325;
      text-decoration: none;
      font-weight: 500;
    }
    .back-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
<div class="admin-container">
  <h1>Edit User: <?=htmlspecialchars($user['email'])?></h1>
  <form method="post" class="edit-form">
    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
    <input type="hidden" name="user_id" value="<?=intval($user['id'])?>">
    <label>
      First Name
      <input type="text" name="first_name" value="<?=htmlspecialchars($user['first_name'])?>">
    </label>
    <label>
      Last Name
      <input type="text" name="last_name" value="<?=htmlspecialchars($user['last_name'])?>">
    </label>
    <label>
      Role
      <select name="role">
        <option <?= $user['role']==='User' ? 'selected' : '' ?>>User</option>
        <option <?= $user['role']==='Admin' ? 'selected' : '' ?>>Admin</option>
      </select>
    </label>
    <label class="checkbox-label">
      <input type="checkbox" name="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?>>
      Active
    </label>
    <label>
      New Password (optional)
      <input type="text" name="new_password" placeholder="Leave blank to keep current password">
      <small style="display: block; color: #6c757d; margin-top: 4px;">Enter new password to reset, or leave blank to keep unchanged</small>
    </label>
    <label class="checkbox-label">
      <input type="checkbox" name="send_reset" value="1">
      Send password reset email
    </label>
    <button type="submit">Save Changes</button>
  </form>
  <p class="back-link"><a href="index.php">← Back to User Management</a></p>
</div>
</body>
</html>
