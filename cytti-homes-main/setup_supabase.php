<?php
/**
 * Setup script to create users table in Supabase PostgreSQL
 * Run this once to set up the database
 */

require_once 'config.php';
require_once 'db_connect.php';

echo "<h1>Database Setup for Supabase</h1>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<p>Connected to database successfully!</p>";
    
    // Check if users table exists
    $result = pg_query($conn, "SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public'
        AND table_name = 'users'
    )");
    
    $row = pg_fetch_assoc($result);
    $tableExists = $row['exists'];
    
    if (!$tableExists) {
        echo "<p>Creating users table...</p>";
        
        // Create users table (PostgreSQL syntax)
        $sql = "CREATE TABLE users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(20) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (pg_query($conn, $sql)) {
            echo "<p style='color: green;'>Users table created successfully!</p>";
        } else {
            echo "<p style='color: red;'>Error creating table: " . pg_last_error($conn) . "</p>";
        }
    } else {
        echo "<p>Users table already exists!</p>";
    }
    
    // Show existing users
    echo "<h2>Existing Users</h2>";
    $result = pg_query($conn, "SELECT id, username, email, created_at FROM users LIMIT 10");
    
    if ($result && pg_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Created At</th></tr>";
        while ($row = pg_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found. Please sign up!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
