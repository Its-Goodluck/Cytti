<?php
require_once 'config.php';
require_once 'db_connect.php';
require_once 'functions.php';

// Simulate POST data
$_POST = [
    'username' => 'testuser',
    'email' => 'test@example.com',
    'password' => 'password123',
    'confirm_password' => 'password123',
    'terms' => 'on'
];

$_SERVER['REQUEST_METHOD'] = 'POST';

echo "<h1>Testing Signup Process</h1>";

// Include the signup_process.php logic here for testing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Sanitize and validate inputs
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);

    echo "<p>Input data: username=$username, email=$email, password length=" . strlen($password) . ", terms=$terms</p>";

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

    echo "<p>Validation errors: " . (empty($errors) ? 'None' : json_encode($errors)) . "</p>";

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
            echo "<p>Duplicate check: " . (empty($errors) ? 'No duplicates' : json_encode($errors)) . "</p>";
        } catch (Exception $e) {
            $errors['general'] = 'Database error occurred. Please try again.';
            echo "<p>Database error in duplicate check: " . $e->getMessage() . "</p>";
        }
    }

    // If errors, show them
    if (!empty($errors)) {
        echo "<p>Final errors: " . json_encode($errors) . "</p>";
    } else {
        // Insert user
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $userId = Database::getInstance()->insert('users', [
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword
            ]);

            echo "<p>User inserted successfully with ID: $userId</p>";

            // Verify the insert worked
            $insertedUser = Database::getInstance()->fetchOne("SELECT id, username, email FROM users WHERE id = ?", [$userId]);

            if (!$insertedUser) {
                throw new Exception("User was not inserted properly");
            }

            echo "<p>User verification successful: " . json_encode($insertedUser) . "</p>";

        } catch (Exception $e) {
            echo "<p>Insert error: " . $e->getMessage() . "</p>";
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
} else {
    echo "<p>Not a POST request</p>";
}
?>
