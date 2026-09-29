<?php
require_once 'config.php';

echo "<h1>Database Connection Test</h1>";

echo "<p>DB_HOST: " . DB_HOST . "</p>";
echo "<p>DB_NAME: " . DB_NAME . "</p>";
echo "<p>DB_USER: " . DB_USER . "</p>";
echo "<p>DB_PASS: " . (empty(DB_PASS) ? 'empty' : 'set') . "</p>";
echo "<p>DB_PORT: " . (defined('DB_PORT') ? DB_PORT : 'default') . "</p>";

try {
    // Test basic MySQL connection
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    if (defined('DB_PORT')) {
        $dsn .= ";port=" . DB_PORT;
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "<p style='color: green;'>✓ Basic MySQL connection successful!</p>";

    // Test database selection
    $pdo->exec("USE " . DB_NAME);
    echo "<p style='color: green;'>✓ Database '" . DB_NAME . "' selected successfully!</p>";

    // Test table existence
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<p>Tables in database:</p><ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

    if (in_array('users', $tables)) {
        echo "<p style='color: green;'>✓ Users table exists!</p>";

        // Check users count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "<p>Current user count: $count</p>";
    } else {
        echo "<p style='color: red;'>✗ Users table does not exist!</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Error code: " . $e->getCode() . "</p>";
}
?>
