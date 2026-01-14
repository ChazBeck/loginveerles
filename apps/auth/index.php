<?php
require __DIR__ . '/include/jwt_include.php';
jwt_init();
jwt_require_login();
$user = jwt_get_user();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Apps Home - Tools</title>
  <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
<?php include __DIR__ . '/../_header.php'; ?>
<main class="hero">
  <div class="container">
    <h1 class="welcome">Welcome, <?=htmlspecialchars($user['first_name'] ?? $user['email'])?></h1>
    <p>Available apps:</p>
    <div class="buttons">
      <a href="../pulse/" class="button">Pulse</a>
      <a href="../url_shortener/" class="button">URL Shortener</a>
    </div>
  </div>
</main>
</body>
</html>
