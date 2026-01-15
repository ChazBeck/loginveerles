<?php
require __DIR__ . '/../include/jwt_include.php';
jwt_init();
jwt_require_admin();

$pdo = auth_db();
$stmt = $pdo->query('SELECT id, email, first_name, last_name, role, is_active, created_at, last_login FROM users ORDER BY email');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<header class="header">
  <a href="../index.php" class="logo">Apps Auth</a>
  <nav>
    <?php if (jwt_is_admin()): ?>
      <a href="<?=htmlspecialchars($_SERVER['PHP_SELF'])?>">ADMIN</a>
    <?php endif; ?>
    <a href="../logout.php">LOG OUT</a>
  </nav>
</header>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - User Management</title>
  <style>
    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
      background: url('https://images.unsplash.com/photo-1518837695005-2083093ee35b?q=80&w=2000') center/cover fixed;
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
      max-width: 1200px;
      margin: 40px auto;
      padding: 20px;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .admin-container h1 {
      color: #043546;
      margin-bottom: 30px;
      font-size: 2rem;
    }
    .admin-container h2 {
      color: #043546;
      margin-top: 30px;
      margin-bottom: 15px;
      font-size: 1.5rem;
      border-bottom: 2px solid #E58325;
      padding-bottom: 10px;
    }
    .admin-form {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 4px;
      margin-bottom: 30px;
    }
    .admin-form label {
      display: block;
      margin-bottom: 15px;
      color: #043546;
      font-weight: 500;
    }
    .admin-form input[type="email"],
    .admin-form input[type="text"],
    .admin-form select {
      width: 100%;
      max-width: 400px;
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
      margin-top: 5px;
    }
    .admin-form button {
      background: #E58325;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      margin-top: 10px;
    }
    .admin-form button:hover {
      background: #d67520;
    }
    .users-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: white;
    }
    .users-table th {
      background: #043546;
      color: white;
      padding: 12px;
      text-align: left;
      font-weight: 600;
    }
    .users-table td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
    }
    .users-table tr:hover {
      background: #f8f9fa;
    }
    .users-table a {
      color: #E58325;
      text-decoration: none;
      font-weight: 500;
    }
    .users-table a:hover {
      text-decoration: underline;
    }
    .users-table button {
      background: #6c757d;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.9rem;
    }
    .users-table button:hover {
      background: #5a6268;
    }
  </style>
</head>
<body>
<div class="admin-container">
  <h1>User Management</h1>
  
  <?php if (isset($_GET['success'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
      ✓ <?=htmlspecialchars($_GET['success'])?>
    </div>
  <?php endif; ?>

  <h2>Add New User</h2>
  <form method="post" action="add_user.php" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
    <label>
      Email
      <input type="email" name="email" required>
    </label>
    <label>
      First Name
      <input type="text" name="first_name">
    </label>
    <label>
      Last Name
      <input type="text" name="last_name">
    </label>
    <label>
      Role
      <select name="role">
        <option>User</option>
        <option>Admin</option>
      </select>
    </label>
    <label>
      Password (optional)
      <input type="text" name="password" placeholder="Leave blank to require password reset">
      <small style="display: block; color: #6c757d; margin-top: 4px;">Set a password to share directly, or leave blank to send reset email</small>
    </label>
    <label style="display: flex; align-items: center;">
      <input type="checkbox" name="send_reset" checked style="width: auto; margin-right: 8px;">
      Send password setup email (only if password is blank)
    </label>
    <button type="submit">Add User</button>
  </form>

  <h2>Existing Users</h2>
  <table class="users-table">
    <thead>
      <tr>
        <th>Email</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Role</th>
        <th>Active</th>
        <th>Last Login</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?=htmlspecialchars($u['email'])?></td>
        <td><?=htmlspecialchars($u['first_name'] ?? '')?></td>
        <td><?=htmlspecialchars($u['last_name'] ?? '')?></td>
        <td><?=htmlspecialchars($u['role'])?></td>
        <td><?= $u['is_active'] ? '✓ Yes' : '✗ No' ?></td>
        <td><?=htmlspecialchars($u['last_login'] ?? 'Never')?></td>
        <td>
          <a href="edit_user.php?user_id=<?=intval($u['id'])?>">Edit</a>
          &nbsp;
          <form method="post" action="toggle_user.php" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
            <input type="hidden" name="user_id" value="<?=intval($u['id'])?>">
            <input type="hidden" name="action" value="<?= $u['is_active'] ? 'disable' : 'enable' ?>">
            <button type="submit"><?= $u['is_active'] ? 'Disable' : 'Enable' ?></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
