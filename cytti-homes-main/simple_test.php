<?php
require_once 'config.php';
require_once 'db_connect.php';

echo "<h1>Simple Database Test</h1>";

try {
    $db = Database::getInstance();

    // Test connection
    echo "<p>Database connection: OK</p>";

    // Test insert
    $userId = $db->insert('users', [
        'username' => 'simpletest',
        'email' => 'simple@test.com',
        'password' => password_hash('test123', PASSWORD_DEFAULT)
    ]);

    echo "<p>User inserted with ID: $userId</p>";

    // Test fetch
    $user = $db->fetchOne("SELECT id, username, email FROM users WHERE id = ?", [$userId]);
    echo "<p>Fetched user: " . json_encode($user) . "</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>";
}
?>
