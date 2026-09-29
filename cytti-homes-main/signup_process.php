<?php
/**
 * Signup Process - User Registration Handler
 * 
 * IMPORTANT: session_start() must be called FIRST, before anything else!
 * This file should NEVER have any output before the redirect headers.
 */

// Start session FIRST - this is critical!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Output buffering to prevent "headers already sent" errors
ob_start();

// Include configuration FIRST
require_once 'config.php';
require_once 'db_connect.php';
require_once 'functions.php';

// Debug: Log incoming request
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("Signup process started - Session ID: " . session_id());
    error_log("POST data received: " . print_r($_POST, true));
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Not a POST request, redirect to signup
    ob_end_clean();
    header("Location: signup.php");
    exit;
}

$errors = [];

// Sanitize and validate inputs
$username = sanitizeInput($_POST['username'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$terms = isset($_POST['terms']);

// Username validation
if (empty($username)) {
    $errors['username'] = 'Username is required';
} elseif (strlen($username) < 3) {
    $errors['username'] = 'Username must be at least 3 characters long';
} elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
    $errors['username'] = 'Username can only contain letters, numbers, hyphens, and underscores';
} elseif (strlen($username) > 20) {
    $errors['username'] = 'Username must be 20 characters or less';
}

// Email validation
if (empty($email)) {
    $errors['email'] = 'Email is required';
} elseif (!validateEmail($email)) {
    $errors['email'] = 'Please enter a valid email address';
} elseif (strlen($email) > 100) {
    $errors['email'] = 'Email must be 100 characters or less';
}

// Password validation
if (empty($password)) {
    $errors['password'] = 'Password is required';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters long';
} elseif (strlen($password) > 255) {
    $errors['password'] = 'Password must be 255 characters or less';
}

// Confirm password validation
if ($password !== $confirm_password) {
    $errors['confirm_password'] = 'Passwords do not match';
}

// Terms validation
if (!$terms) {
    $errors['terms'] = 'You must agree to the Terms of Service';
}

// If there are validation errors, redirect back
if (!empty($errors)) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Validation errors: " . print_r($errors, true));
    }
    
    $_SESSION['signup_errors'] = $errors;
    $_SESSION['signup_data'] = [
        'username' => $username,
        'email' => $email,
        'terms' => $terms
    ];
    
    // Clear the output buffer and redirect
    ob_end_clean();
    header("Location: signup.php");
    exit;
}

// If no validation errors, check for duplicate username/email in database
if (empty($errors)) {
    try {
        $existingUser = Database::getInstance()->fetchOne(
            "SELECT id, email, username FROM users WHERE email = ? OR username = ?",
            [$email, $username]
        );

        if ($existingUser) {
            if ($existingUser['email'] === $email) {
                $errors['email'] = 'This email is already registered. Try logging in instead?';
            }
            if ($existingUser['username'] === $username) {
                $errors['username'] = 'This username is taken. How about adding a number or initial?';
            }
        }

    } catch (Exception $e) {
        error_log("Signup duplicate check error: " . $e->getMessage());
        $errors['general'] = 'Database error occurred. Please try again.';
    }
}

// If errors exist after duplicate check, redirect back
if (!empty($errors)) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Duplicate check errors: " . print_r($errors, true));
    }
    
    $_SESSION['signup_errors'] = $errors;
    $_SESSION['signup_data'] = [
        'username' => $username,
        'email' => $email,
        'terms' => $terms
    ];
    
    // Clear the output buffer and redirect
    ob_end_clean();
    header("Location: signup.php");
    exit;
}

// All validation passed - Insert user into database
try {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Attempting to insert user: $username, $email");
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $userId = Database::getInstance()->insert('users', [
        'username' => $username,
        'email' => $email,
        'password' => $hashedPassword
    ]);

    if (!$userId) {
        throw new Exception("Insert failed: userId not returned.");
    }

    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("User inserted successfully. User ID: $userId");
    }

    // Set a success flash and redirect user to login page
    $_SESSION['signup_success'] = 'Account created successfully. Please log in.';

    ob_end_clean();
    header("Location: login.php");
    exit;

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    $_SESSION['signup_errors'] = [
        'general' => 'Registration failed. Please try again later. Error: ' . (defined('DEBUG_MODE') && DEBUG_MODE ? $e->getMessage() : 'Internal error')
    ];
    $_SESSION['signup_data'] = [
        'username' => $username,
        'email' => $email,
        'terms' => $terms
    ];

    // Clear the output buffer and redirect
    ob_end_clean();
    header("Location: signup.php");
    exit;
}
?>
