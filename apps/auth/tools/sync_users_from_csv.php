<?php
// sync_users_from_csv.php
// Updates existing users in the DB with first_name, last_name, role read from users.csv
// Usage (CLI or browser):
//   php sync_users_from_csv.php
// It will only UPDATE existing users (matched by email). It will not overwrite existing non-empty names unless you pass ?force=1 in browser or --force on CLI.

$cfgPath = __DIR__ . '/../config.php';
if (!file_exists($cfgPath)) {
    die("Please create config.php based on config.sample.php\n");
}
$config = require $cfgPath;
$csv = __DIR__ . '/../../../users.csv';
if (!file_exists($csv)) {
    die("users.csv not found at $csv\n");
}

$force = false;
if (php_sapi_name() === 'cli') {
    $force = in_array('--force', $argv);
} else {
    $force = isset($_GET['force']) && $_GET['force'] == '1';
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
$header = fgetcsv($handle, 0, ',', '"', '\\');
$map = [];
foreach ($header as $i => $h) $map[strtolower(trim($h))] = $i;

$select = $pdo->prepare('SELECT id, first_name, last_name, role FROM users WHERE email = :email LIMIT 1');
$update = $pdo->prepare('UPDATE users SET first_name = :fn, last_name = :ln, role = :role WHERE id = :id');

$updated = 0;
$skipped = 0;
$created = 0;
while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
    $email = $row[$map['email']] ?? null;
    if (!$email) continue;
    $fn = '';
    $ln = '';
    $role = 'User';
    if (isset($map['firstname'])) $fn = trim($row[$map['firstname']] ?? '');
    if (isset($map['lastname'])) $ln = trim($row[$map['lastname']] ?? '');
    if (isset($map['role'])) $role = trim($row[$map['role']] ?? 'User');

    $select->execute([':email' => $email]);
    $u = $select->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $need = false;
        $newFn = $u['first_name'];
        $newLn = $u['last_name'];
        $newRole = $u['role'];
        if ($force || empty($u['first_name'])) { $newFn = $fn; $need = true; }
        if ($force || empty($u['last_name'])) { $newLn = $ln; $need = true; }
        if ($force || empty($u['role'])) { $newRole = $role; $need = true; }
        if ($need) {
            $update->execute([':fn'=>$newFn, ':ln'=>$newLn, ':role'=>$newRole, ':id'=>$u['id']]);
            $updated++;
            if (php_sapi_name() !== 'cli') echo "Updated: " . htmlentities($email) . "<br>\n";
            else echo "Updated: $email\n";
        } else {
            $skipped++;
        }
    } else {
        // user not found — skip by default
        $skipped++;
    }
}
fclose($handle);

if (php_sapi_name() === 'cli') {
    echo "Done. Updated: $updated; Skipped: $skipped; Created: $created\n";
} else {
    echo "Done. Updated: $updated; Skipped: $skipped; Created: $created<br>\n";
}
