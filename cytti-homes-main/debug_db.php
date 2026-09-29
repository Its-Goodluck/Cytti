<?php
require_once 'config.php';
require_once 'db_connect.php';

echo "<h1>Database Debug</h1>";

try {
    $db = Database::getInstance()->getPDO();
    echo "<p>Database connection successful!</p>";

    // Test select
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Users table exists. Current user count: " . $result['count'] . "</p>";

    // Test insert
    echo "<p>Testing insert...</p>";
    $testUserId = Database::getInstance()->insert('users', [
        'username' => 'testuser_' . time(),
        'email' => 'test' . time() . '@example.com',
        'password' => password_hash('testpass', PASSWORD_DEFAULT)
    ]);
    echo "<p>Test user inserted with ID: $testUserId</p>";

    // Verify insert
    $user = Database::getInstance()->fetchOne("SELECT id, username, email FROM users WHERE id = ?", [$testUserId]);
    if ($user) {
        echo "<p>User verification successful: " . htmlspecialchars($user['username']) . "</p>";
    } else {
        echo "<p>User verification failed!</p>";
    }

} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>";
}
?>
