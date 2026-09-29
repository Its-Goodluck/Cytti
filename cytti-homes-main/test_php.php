<?php
/**
 * Simple PHP Test File for InfinityFree
 * This file tests if PHP is working correctly on the server
 */

// Display PHP version
echo "PHP Version: " . phpversion() . "\n";

// Display loaded extensions
echo "\nLoaded Extensions:\n";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo "- " . $ext . "\n";
}

// Check required extensions
echo "\nRequired Extensions Check:\n";
$required_extensions = ['mysqli', 'pdo', 'pdo_mysql', 'mbstring', 'json', 'curl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "[OK] $ext is loaded\n";
    } else {
        echo "[FAIL] $ext is NOT loaded\n";
    }
}

// Display server info
echo "\nServer Information:\n";
echo "Server API: " . php_sapi_name() . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";

// Display InfinityFree database configuration
echo "\nInfinityFree Database Configuration:\n";
echo "Host: sql213.infinityfree.com\n";
echo "User: if0_41198744\n";
echo "Database: if0_41198744_cytti\n";
echo "Port: 3306\n";

// Test database connection
echo "\nDatabase Connection Test:\n";
require_once 'db_connect.php';
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    if ($conn->ping()) {
        echo "[OK] Database connection successful!\n";
    } else {
        echo "[FAIL] Database connection failed\n";
    }
} catch (Exception $e) {
    echo "[FAIL] Database error: " . $e->getMessage() . "\n";
}

echo "\nSITE WORKING\n";
?>
