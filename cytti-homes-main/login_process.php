<?php
/**
 * Login Process - User Authentication Handler
 * 
 * IMPORTANT: config.php handles session and output buffering
 * Must be included FIRST before any other code or output
 */

// Include configuration - this handles session and output buffering
require_once 'config.php';
require_once 'db_connect.php';
require_once 'functions.php';

// Debug: Log incoming request
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("Login process started - Session ID: " . session_id());
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Not a POST request, redirect to login
    ob_end_clean();
    header("Location: login.php");
    exit;
}

$errors = [];

// Sanitize and validate inputs
$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validation
if (empty($email)) {
    $errors['email'] = 'Email is required';
} elseif (!validateEmail($email)) {
    $errors['email'] = 'Please enter a valid email address';
}

if (empty($password)) {
    $errors['password'] = 'Password is required';
}

// If validation errors, redirect back
if (!empty($errors)) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Login validation errors: " . print_r($errors, true));
    }
    
    // Store errors in session
    $_SESSION['login_errors'] = $errors;
    $_SESSION['login_email'] = $email;
    
    ob_end_clean();
    header("Location: login.php");
    exit;
}

// Get user from database
try {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Attempting login for: $email");
    }
    
    $user = Database::getInstance()->fetchOne(
        "SELECT id, username, email, password FROM users WHERE email = ?",
        [$email]
    );

    if (!$user || !verifyPassword($password, $user['password'])) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Login failed - Invalid credentials for: $email");
        }
        
        $errors['general'] = 'Invalid email or password';
        $_SESSION['login_errors'] = $errors;
        
        ob_end_clean();
        header("Location: login.php");
        exit;
    }

    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    // Set session fingerprint for security (explicit session handling)
    $_SESSION['fingerprint'] = [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'created' => time()
    ];
    
    // Set session activity tracking
    $_SESSION['last_activity'] = time();
    $_SESSION['last_page'] = 'login_process.php';
    $_SESSION['last_regeneration'] = time();

    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Login successful - User ID: " . $user['id'] . ", Session ID: " . session_id());
    }

    // Clear the output buffer and redirect to dashboard
    ob_end_clean();
    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    $errors['general'] = 'Login failed. Please try again later.';
    $_SESSION['login_errors'] = $errors;
    
    ob_end_clean();
    header("Location: login.php");
    exit;
}
?>
