<?php
/**
 * Logout Process - User Logout Handler
 * 
 * IMPORTANT: session_start() must be called FIRST, before anything else!
 */

// Start session FIRST
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Output buffering to prevent "headers already sent" errors
ob_start();

// Include configuration
require_once 'config.php';

// Debug: Log logout
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("User logout - Session ID: " . session_id() . ", User ID: " . ($_SESSION['user_id'] ?? 'unknown'));
}

// Destroy the session
$_SESSION = [];
session_destroy();

// Clear the output buffer and redirect to login
ob_end_clean();
header('Location: login.php');
exit;
?>
