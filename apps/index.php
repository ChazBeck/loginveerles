<?php
// Main Apps Dashboard - v1.0
require __DIR__ . '/auth/include/auth_include.php';
auth_init();
auth_require_login();
$user = auth_get_user();
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Apps</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include __DIR__ . '/_header.php'; ?>
<main class="hero">
  <div class="container">
    <div class="welcome">Welcome</div>
    <h1>Apps Portal</h1>
    <p>Hello <?=htmlspecialchars($user['first_name'] ?: $user['email'] ?? '')?> — choose an app below.</p>
    <div class="buttons">
      <a class="button" href="url_shortener/">URL Shortener</a>
      <a class="button" href="pulse/">Pulse</a>
    </div>
  </div>
</main>
</body>
</html>
