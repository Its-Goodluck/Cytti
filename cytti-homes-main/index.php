<?php
/**
 * Index page - redirects to properties listing
 * This is the main entry point for the website
 * 
 * FIXED: Proper session handling and error display
 */

// Remove any output buffering first
while (ob_get_level()) {
    ob_end_clean();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start session properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once 'config.php';

// Debug: Log index access
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("Index page accessed - Session ID: " . session_id());
}

// Redirect to properties listing page
header("Location: properties.html");
exit;
?>
