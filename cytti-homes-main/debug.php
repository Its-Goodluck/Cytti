<?php
/**
 * Debug Bootstrap - Load this first to diagnose issues
 * Include this at the top of any file to diagnose problems
 */

// Enable maximum error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start output buffering
ob_start();

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log function for debugging
function debugLog($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= " - " . print_r($data, true);
    }
    error_log($log);
    echo "<!-- DEBUG: " . htmlspecialchars($log) . " -->\n";
}

// Check if config is already loaded
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true);
}

debugLog("Debug bootstrap loaded");
debugLog("PHP Version", phpversion());
debugLog("Session ID", session_id());
debugLog("Request URI", $_SERVER['REQUEST_URI'] ?? 'N/A');
?>
