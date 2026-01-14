<?php
// revoke_sessions.php
// Deletes sessions for a given user email (CLI or web).
// Usage CLI: php revoke_sessions.php charlie@veerless.com
// Usage web: revoke_sessions.php?email=charlie@veerless.com

if (file_exists(__DIR__ . '/../config.php')) {
    $config = require __DIR__ . '/../config.php';
} else {
    $config = require __DIR__ . '/../config.sample.php';
}

try {
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    echo "DB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$email = $argv[1] ?? ($_GET['email'] ?? null);
if (!$email) {
    echo "Usage: php revoke_sessions.php email@domain.com\n";
    exit(1);
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
$stmt->execute([':e' => $email]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$u) {
    echo "User not found: $email\n";
    exit(1);
}
$uid = $u['id'];
$del = $pdo->prepare('DELETE FROM sessions WHERE user_id = :uid');
$del->execute([':uid' => $uid]);
$count = $del->rowCount();
echo "Deleted $count session(s) for $email (user id: $uid)\n";

// advise: clear cookie in browser
echo "If you used a browser, please clear cookies or close the browser to remove stale session cookie.\n";
