<?php
/**
 * Debug Database Connection
 * Use this to diagnose connection issues
 */

require_once 'config.php';
require_once 'db_connect.php';

echo "<h1>Database Connection Debug</h1>";

echo "<h2>Connection Details:</h2>";
echo "<pre>";
echo "Host: " . $GLOBALS['host'] . "\n";
echo "Port: " . $GLOBALS['port'] . "\n";
echo "Database: " . $GLOBALS['db'] . "\n";
echo "User: " . $GLOBALS['user'] . "\n";
echo "</pre>";

echo "<h2>Testing Connection:</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✓ Database connected successfully!</p>";
        
        // Check if users table exists
        $result = pg_query($conn, "SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public'
            AND table_name = 'users'
        )");
        
        if ($result) {
            $row = pg_fetch_assoc($result);
            $tableExists = $row['exists'];
            
            if ($tableExists) {
                echo "<p style='color: green;'>✓ Users table exists!</p>";
                
                // Count users
                $result = pg_query($conn, "SELECT COUNT(*) as count FROM users");
                $row = pg_fetch_assoc($result);
                echo "<p>Current users: " . $row['count'] . "</p>";
                
                // Test insert
                echo "<h3>Testing Insert:</h3>";
                $testUsername = 'test_' . time();
                $testEmail = 'test' . time() . '@example.com';
                
                $sql = "INSERT INTO users (username, email, password) VALUES ($1, $2, $3) RETURNING id";
                $result = pg_query_params($conn, $sql, [
                    $testUsername,
                    $testEmail,
                    password_hash('test123', PASSWORD_DEFAULT)
                ]);
                
                if ($result) {
                    $row = pg_fetch_assoc($result);
                    $insertId = $row['id'];
                    echo "<p style='color: green;'>✓ Test insert successful! User ID: $insertId</p>";
                    
                    // Clean up
                    pg_query_params($conn, "DELETE FROM users WHERE id = $1", [$insertId]);
                    echo "<p>Test user cleaned up.</p>";
                } else {
                    echo "<p style='color: red;'>✗ Insert failed: " . pg_last_error($conn) . "</p>";
                }
            } else {
                echo "<p style='color: red;'>✗ Users table does not exist!</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Failed to check tables: " . pg_last_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Connection returned null</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
