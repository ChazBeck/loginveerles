<?php
require __DIR__ . '/../include/auth_include.php';
auth_init();
auth_require_admin();

$pdo = auth_db();
$stmt = $pdo->query('SELECT id, email, first_name, last_name, role, is_active, created_at, last_login FROM users ORDER BY email');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - User Management</title>
  <link rel="stylesheet" href="../../assets/styles.css">
  <style>
    .admin-container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .admin-header {
      background: #043546;
      color: #FFFFFF;
      padding: 20px;
      border-radius: 6px;
      margin-bottom: 30px;
    }
    .admin-header h1 {
      margin: 0 0 10px 0;
      font-family: 'Wayfinder CF', serif;
      font-size: 2rem;
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
    .admin-form input[type="email"],
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
    .admin-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .admin-table th {
      background: #043546;
      color: #FFFFFF;
      padding: 12px;
      text-align: left;
      font-family: 'Archivo', sans-serif;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.9rem;
    }
    .admin-table td {
      padding: 12px;
      border-bottom: 1px solid #CAC1A9;
    }
    .admin-table tr:hover {
      background-color: #f5f5f5;
    }
    .admin-table a {
      color: #E58325;
      text-decoration: none;
      font-weight: 600;
    }
    .admin-table a:hover {
      color: #00434F;
    }
    .admin-table button {
      padding: 6px 12px;
      background-color: #043546;
      color: #FFFFFF;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.85rem;
    }
    .admin-table button:hover {
      background-color: #E58325;
    }
    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .badge-active {
      background-color: #4CAF50;
      color: white;
    }
    .badge-inactive {
      background-color: #E53935;
      color: white;
    }
    .badge-admin {
      background-color: #E58325;
      color: white;
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
  <div class="admin-header">
    <h1>👤 User Management</h1>
    <p style="margin: 0;">Signed in as <?=htmlspecialchars(auth_get_user()['email'])?></p>
  </div>

  <div class="admin-section">
    <h2>➕ Add New User</h2>
    <form method="post" action="add_user.php" class="admin-form">
      <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
      <label>Email Address *
        <input type="email" name="email" required placeholder="user@example.com">
      </label>
      <label>First Name
        <input type="text" name="first_name" placeholder="John">
      </label>
      <label>Last Name
        <input type="text" name="last_name" placeholder="Doe">
      </label>
      <label>Role
        <select name="role">
          <option value="User">User</option>
          <option value="Admin">Admin</option>
        </select>
      </label>
      <label style="font-weight: normal;">
        <input type="checkbox" name="send_reset" checked style="width: auto; margin-right: 8px;">
        Send password setup email
      </label>
      <button type="submit">Add User</button>
    </form>
  </div>

  <div class="admin-section">
    <h2>📋 Existing Users (<?= count($users) ?>)</h2>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Email</th>
          <th>First Name</th>
          <th>Last Name</th>
          <th>Role</th>
          <th>Status</th>
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
          <td>
            <?php if (strcasecmp($u['role'], 'Admin') === 0): ?>
              <span class="badge badge-admin">Admin</span>
            <?php else: ?>
              <?=htmlspecialchars($u['role'])?>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge <?= $u['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
              <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td><?=htmlspecialchars($u['last_login'] ?: 'Never')?></td>
          <td>
            <a href="edit_user.php?user_id=<?=intval($u['id'])?>">Edit</a>
            &nbsp;|&nbsp;
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
</div>
</body>
</html>
