<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/include/common_functions.php';

echo "<h2>Check password_resets Table Schema</h2>";
echo "<style>body{font-family:monospace;padding:20px;}</style>";

$pdo = auth_db();

try {
    $stmt = $pdo->query('DESCRIBE password_resets');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Columns:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Sample Data:</h3>";
    $stmt = $pdo->query('SELECT * FROM password_resets LIMIT 3');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($rows) {
        echo "<pre>";
        print_r($rows);
        echo "</pre>";
    } else {
        echo "<p>No data in table</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
