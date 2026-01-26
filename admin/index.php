<?php
require __DIR__ . '/../include/jwt_include.php';
require __DIR__ . '/../include/render_header.php';

jwt_init();
jwt_require_admin();

$pdo = auth_db();
$stmt = $pdo->query('SELECT id, email, first_name, last_name, role, is_active, created_at, last_login, avatar_url FROM users ORDER BY email');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

render_page_head('Admin - User Management', ['pathPrefix' => '../', 'includeAdmin' => true]);
render_app_header(['pathPrefix' => '../', 'homeUrl' => '../index.php']);
?>

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
