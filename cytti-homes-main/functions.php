<?php
/**
 * Helper Functions for Cytti
 * 
 * IMPORTANT: Always start session before using session functions
 */

// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Sanitize input - prevent XSS
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Redirect if not logged in
 * Uses output buffering to prevent "headers already sent" errors
 */
function requireLogin() {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isLoggedIn()) {
        // Use output buffering to prevent header errors
        ob_start();
        header('Location: login.php');
        ob_end_flush();
        exit;
    }
}

/**
 * Get current user data
 */
function getCurrentUser() {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null
    ];
}

/**
 * Log out user and destroy session
 */
function logout() {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login
    ob_start();
    header('Location: login.php');
    ob_end_flush();
    exit;
}

/**
 * Redirect to a specific page
 */
function redirectTo($location) {
    // Use output buffering to prevent header errors
    ob_start();
    header('Location: ' . $location);
    ob_end_flush();
    exit;
}

/**
 * Set flash message for next request
 */
function setFlashMessage($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and clear flash message
 */
function getFlashMessage($type) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}
?>
