<?php
require_once 'config.php';
require_once 'db_connect.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Sanitize and validate inputs
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);

    // Validation
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username must be at least 3 characters long';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        $errors['username'] = 'Username can only contain letters, numbers, hyphens, and underscores';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    if (!$terms) {
        $errors['terms'] = 'You must agree to the Terms of Service';
    }

    // Check for duplicate username/email if no other errors
    if (empty($errors)) {
        try {
            $existingUser = Database::getInstance()->fetchOne(
                "SELECT id FROM users WHERE email = ? OR username = ?",
                [$email, $username]
            );

            if ($existingUser) {
                if (Database::getInstance()->fetchOne("SELECT id FROM users WHERE email = ?", [$email])) {
                    $errors['email'] = 'An account with this email already exists';
                } else {
                    $errors['username'] = 'This username is already taken';
                }
            }
        } catch (Exception $e) {
            $errors['general'] = 'Database error occurred. Please try again.';
        }
    }

    // If errors, redirect back with errors
    if (!empty($errors)) {
        $error_string = http_build_query($errors);
        header("Location: signup.php?errors=1&$error_string");
        exit;
    }

    // Insert user
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $userId = Database::getInstance()->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        // Verify the insert worked
        $insertedUser = Database::getInstance()->fetchOne("SELECT id, username, email FROM users WHERE id = ?", [$userId]);

        if (!$insertedUser) {
            throw new Exception("User was not inserted properly");
        }

        // Start session and log user in
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;

        // Redirect to dashboard
        header('Location: dashboard.php');
        exit;

    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        $errors['general'] = 'Registration failed. Please try again.';
        $error_string = http_build_query($errors);
        header("Location: signup.php?errors=1&$error_string");
        exit;
    }
} else {
    // Not a POST request
    header('Location: signup.php');
    exit;
}
?>
