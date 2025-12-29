<?php
require __DIR__ . '/include/auth_include.php';
auth_init();
auth_require_login();
$user = auth_get_user();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Apps Home</title></head>
<body>
<h1>Welcome, <?=htmlspecialchars($user['first_name'] ?? $user['email'])?></h1>
<p>Available apps:</p>
<ul>
  <li><a href="/apps/example_app">Example App</a></li>
  <!-- Add links to your apps here -->
</ul>
<p><a href="logout.php">Logout</a></p>
</body></html>
