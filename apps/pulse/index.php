<?php
require __DIR__ . '/../auth/include/auth_include.php';
auth_init();
auth_require_login();
$user = auth_get_user();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Pulse</title>
  <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
<?php include __DIR__ . '/../_header.php'; ?>
<main class="hero">
  <div class="container">
    <h1>Pulse</h1>
    <p>Placeholder page for the Pulse app.</p>
  </div>
</main>
</body>
</html>
