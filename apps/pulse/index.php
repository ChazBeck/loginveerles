<?php
require __DIR__ . '/../auth/include/jwt_include.php';
jwt_init();
jwt_require_login();
$user = jwt_get_user();
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
