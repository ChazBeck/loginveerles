<?php
// Simple CSV importer for users.csv -> users table
// Usage: run in browser or CLI on local XAMPP after creating config.php and importing schema.

$cfgPath = __DIR__ . '/../config.php';
if (!file_exists($cfgPath)) {
    die("Please create config.php based on config.sample.php\n");
}
$config = require $cfgPath;
$csv = __DIR__ . '/../../../users.csv';
if (!file_exists($csv)) {
    die("users.csv not found at $csv\n");
}

try {
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die('DB connect error: ' . $e->getMessage());
}

$handle = fopen($csv, 'r');
if (!$handle) die('Failed to open CSV');
$header = fgetcsv($handle);
$map = [];
foreach ($header as $i => $h) $map[strtolower(trim($h))] = $i;

$insert = $pdo->prepare('INSERT INTO users (email, password_hash, first_name, last_name, role, created_at) VALUES (:email, NULL, :fn, :ln, :role, NOW())');
$check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$count = 0;
while (($row = fgetcsv($handle)) !== false) {
    $email = $row[$map['email']] ?? null;
    if (!$email) continue;
    $check->execute([':email' => $email]);
    if ($check->fetch()) {
        echo "Skipping existing: $email\n";
        continue;
    }
    $fn = $row[$map['firstname']] ?? null;
    $ln = $row[$map['lastname']] ?? null;
    $role = $row[$map['role']] ?? 'User';
    $insert->execute([':email'=>$email, ':fn'=>$fn, ':ln'=>$ln, ':role'=>$role]);
    $uid = $pdo->lastInsertId();
    // create a password reset token so user can set password (expires in 1 hour)
    $token = bin2hex(random_bytes(32));
    $expires = (new DateTime())->add(new DateInterval('PT60M'))->format('Y-m-d H:i:s');
    $insReset = $pdo->prepare('INSERT INTO password_resets (user_id, reset_token, expires_at, created_at) VALUES (:uid, :t, :exp, NOW())');
    $insReset->execute([':uid' => $uid, ':t' => $token, ':exp' => $expires]);
    $resetUrl = rtrim($config['base_url'], '/') . '/password_reset_form.php?token=' . urlencode($token);
    if (php_sapi_name() === 'cli') {
        echo "Imported: $email\nReset URL: $resetUrl\n\n";
    } else {
        echo "<div>Imported: " . htmlentities($email) . " — <a href=\"" . htmlentities($resetUrl) . "\">Set password</a></div>\n";
    }
    $count++;
}
fclose($handle);
echo "Done. Imported $count users.\n";
