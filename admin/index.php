<?php
require __DIR__ . '/../include/jwt_include.php';
jwt_init();
jwt_require_admin();

$pdo = auth_db();
$stmt = $pdo->query('SELECT id, email, first_name, last_name, role, is_active, created_at, last_login, avatar_url FROM users ORDER BY email');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user data for header including custom avatar
$user = jwt_get_user();
$userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

// Get custom avatar from database
$avatarStmt = $pdo->prepare('SELECT avatar_url FROM users WHERE id = :id LIMIT 1');
$avatarStmt->execute([':id' => $user['id']]);
$avatarData = $avatarStmt->fetch(PDO::FETCH_ASSOC);

if ($avatarData && !empty($avatarData['avatar_url'])) {
    $userAvatar = '../' . $avatarData['avatar_url'];
} else {
    $userAvatar = 'https://ui-avatars.com/api/?' . http_build_query([
        'name' => $userName,
        'background' => '667eea',
        'color' => 'fff',
        'size' => '128',
        'bold' => 'true'
    ]);
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - User Management</title>
  <link rel="icon" type="image/png" href="../assets/images/veerless-logo-mark-sunrise-rgb-1920px-w-144ppi.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/ChazBeck/sharedheader@main/header.css">
  <style>
    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
      background: url('../assets/images/v2osk-214954-unsplash.jpg') center/cover fixed;
      min-height: 100vh;
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
    .tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 30px;
      border-bottom: 2px solid #ddd;
    }
    .tab {
      padding: 12px 24px;
      background: none;
      border: none;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      color: #6c757d;
      border-bottom: 3px solid transparent;
      transition: all 0.2s;
    }
    .tab:hover {
      color: #043546;
    }
    .tab.active {
      color: #E58325;
      border-bottom-color: #E58325;
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }
    /* Navigation styling */
    .header-nav {
      display: flex;
      gap: 20px;
      align-items: center;
      margin-left: auto;
      margin-right: 20px;
    }
    .header-nav a {
      color: white !important;
      text-decoration: none;
      font-weight: 600;
      font-size: 1rem;
      padding: 10px 20px;
      border-radius: 4px;
      transition: background 0.2s;
      background: rgba(255, 255, 255, 0.1);
    }
    .header-nav a:hover {
      background: rgba(255, 255, 255, 0.2);
    }
  </style>
</head>
<body>
<!-- Shared Header with User Avatar -->
<header class="shared-header">
    <div class="header-container">
        <div class="header-logo">
            <a href="/apps/">
                <img src="../assets/images/veerless-logo-sunrise-rgb-1920px-w-144ppi.png" alt="Veerless Logo" class="logo-image" style="height: 50px;">
            </a>
        </div>
        <nav class="header-nav">
            <a href="../index.php">Home</a>
        </nav>
        <div class="header-right">
            <div class="user-avatar" style="display: flex;">
                <img src="<?= htmlspecialchars($userAvatar) ?>" alt="User Avatar" class="avatar-image">
                <span class="user-name"><?= htmlspecialchars($userName) ?></span>
            </div>
            <button class="auth-button" onclick="window.location.href='../logout.php'">
                <span>Logout</span>
            </button>
        </div>
    </div>
</header>

<div class="admin-container">
  <h1>User Management</h1>
  
  <?php if (isset($_GET['success'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
      ✓ <?=htmlspecialchars($_GET['success'])?>
    </div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab active" onclick="showTab('active')">Active Users (<?= count(array_filter($users, fn($u) => $u['is_active'])) ?>)</button>
    <button class="tab" onclick="showTab('disabled')">Disabled Users (<?= count(array_filter($users, fn($u) => !$u['is_active'])) ?>)</button>
  </div>

  <!-- Active Users Tab -->
  <div id="active-tab" class="tab-content active">
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

  <h2>Active Users</h2>
  <table class="users-table">
    <thead>
      <tr>
        <th>Email</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Role</th>
        <th>Last Login</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <?php if (!$u['is_active']) continue; ?>
      <tr>
        <td><?=htmlspecialchars($u['email'])?></td>
        <td><?=htmlspecialchars($u['first_name'] ?? '')?></td>
        <td><?=htmlspecialchars($u['last_name'] ?? '')?></td>
        <td><?=htmlspecialchars($u['role'])?></td>
        <td><?=htmlspecialchars($u['last_login'] ?? 'Never')?></td>
        <td>
          <a href="edit_user.php?user_id=<?=intval($u['id'])?>">Edit</a>
          &nbsp;|&nbsp;
          <a href="upload_avatar.php?user_id=<?=intval($u['id'])?>">Avatar</a>
          &nbsp;|&nbsp;
          <form method="post" action="toggle_user.php" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
            <input type="hidden" name="user_id" value="<?=intval($u['id'])?>">
            <input type="hidden" name="action" value="disable">
            <button type="submit">Disable</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <!-- Disabled Users Tab -->
  <div id="disabled-tab" class="tab-content">
  <h2>Disabled Users</h2>
  <table class="users-table">
    <thead>
      <tr>
        <th>Email</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Role</th>
        <th>Last Login</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <?php if ($u['is_active']) continue; ?>
      <tr>
        <td><?=htmlspecialchars($u['email'])?></td>
        <td><?=htmlspecialchars($u['first_name'] ?? '')?></td>
        <td><?=htmlspecialchars($u['last_name'] ?? '')?></td>
        <td><?=htmlspecialchars($u['role'])?></td>
        <td><?=htmlspecialchars($u['last_login'] ?? 'Never')?></td>
        <td>
          <a href="edit_user.php?user_id=<?=intval($u['id'])?>">Edit</a>
          &nbsp;|&nbsp;
          <a href="upload_avatar.php?user_id=<?=intval($u['id'])?>">Avatar</a>
          &nbsp;|&nbsp;
          <form method="post" action="toggle_user.php" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(auth_csrf_token())?>">
            <input type="hidden" name="user_id" value="<?=intval($u['id'])?>">
            <input type="hidden" name="action" value="enable">
            <button type="submit">Enable</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<script>
function showTab(tabName) {
  // Hide all tabs
  document.querySelectorAll('.tab-content').forEach(tab => {
    tab.classList.remove('active');
  });
  document.querySelectorAll('.tab').forEach(tab => {
    tab.classList.remove('active');
  });
  
  // Show selected tab
  document.getElementById(tabName + '-tab').classList.add('active');
  event.target.classList.add('active');
}
</script>
</body>
</html>
