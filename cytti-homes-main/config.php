<?php
/**
 * Configuration file
 * MUST be included at the TOP of every PHP file
 */

// Start output buffering immediately to prevent header errors
if (ob_get_level() === 0) {
    ob_start();
}

// Enable error reporting (disable in production if needed)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// ============================================================
// SESSION CONFIGURATION (SAFE FOR RENDER / CLOUD)
// ============================================================

if (session_status() === PHP_SESSION_NONE) {

    // Determine a writable session path
    $session_save_path = session_save_path();

    if (
        empty($session_save_path) ||
        !is_dir($session_save_path) ||
        !is_writable($session_save_path)
    ) {
        // Try system temp directory
        $session_save_path = sys_get_temp_dir();

        // If still not writable, create local sessions directory
        if (!is_dir($session_save_path) || !is_writable($session_save_path)) {
            $session_dir = __DIR__ . '/sessions';

            if (!is_dir($session_dir)) {
                @mkdir($session_dir, 0755, true);
            }

            if (is_dir($session_dir) && is_writable($session_dir)) {
                $session_save_path = $session_dir;
            }
        }
    }

    // Apply session settings BEFORE starting session
    ini_set('session.save_handler', 'files');
    ini_set('session.save_path', $session_save_path);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_lifetime', 86400);     // 24 hours
    ini_set('session.gc_maxlifetime', 86400);      // 24 hours

    // Optional: Force secure cookies in HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }

    // Now safely start session
    session_start();
}

// ============================================================
// DEBUG LOGGER
// ============================================================

function logDebug($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= " - " . print_r($data, true);
    }
    error_log($logMessage);
}

// Debug mode toggle
define('DEBUG_MODE', true);