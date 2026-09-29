<?php
/**
 * Test Database Connection
 * Use this to verify Supabase connection is working
 */

require_once 'config.php';
require_once 'db_connect.php';

echo "<h1>Database Connection Test</h1>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<p style='color: green;'>✓ Database connected successfully!</p>";
    
    // Check if users table exists
    $result = pg_query($conn, "SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public'
        AND table_name = 'users'
    )");
    
    $row = pg_fetch_assoc($result);
    $tableExists = $row['exists'];
    
    if ($tableExists) {
        echo "<p style='color: green;'>✓ Users table exists!</p>";
        
        // Count users
        $result = pg_query($conn, "SELECT COUNT(*) as count FROM users");
        $row = pg_fetch_assoc($result);
        echo "<p>Current users in database: " . $row['count'] . "</p>";
        
        // Test insert
        echo "<p>Testing user insert...</p>";
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
            echo "<p style='color: green;'>✓ Test user inserted with ID: $insertId</p>";
            
            // Verify
            $result = pg_query_params($conn, "SELECT id, username, email FROM users WHERE id = $1", [$insertId]);
            $user = pg_fetch_assoc($result);
            
            if ($user) {
                echo "<p style='color: green;'>✓ User verified: " . htmlspecialchars($user['username']) . "</p>";
                
                // Delete test user
                pg_query_params($conn, "DELETE FROM users WHERE id = $1", [$insertId]);
                echo "<p>Test user cleaned up.</p>";
            }
            
            echo "<p><strong>Signup should work! Try signing up now.</strong></p>";
        } else {
            echo "<p style='color: red;'>✗ Insert failed: " . pg_last_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Users table does not exist!</p>";
        echo "<p>Please create the table in Supabase first.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
