<?php
/**
 * Signup Page - User Registration Form
 * 
 * FIXED: Session is now handled by config.php
 */

// Include configuration - this handles session and output buffering
require_once 'config.php';
require_once 'functions.php';

// Get any errors and data from session
$errors = $_SESSION['signup_errors'] ?? [];
$data = $_SESSION['signup_data'] ?? [];

// Clear the session variables after retrieving them
unset($_SESSION['signup_errors']);
unset($_SESSION['signup_data']);

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

// Debug: Log if there were errors
if (defined('DEBUG_MODE') && DEBUG_MODE && !empty($errors)) {
    error_log("Signup page loaded with errors: " . print_r($errors, true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Account - Sign Up</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      position: relative;
      overflow: hidden;
    }

    body::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
      z-index: 0;
    }

    .signup-container {
      position: relative;
      z-index: 2;
      width: 100%;
      max-width: 420px;
      padding: 20px;
    }

    .signup-card {
      padding: 40px;
      border-radius: 20px;
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0px 20px 60px rgba(0, 0, 0, 0.3);
      color: #222;
      animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .signup-card h1 {
      font-size: 28px;
      margin-bottom: 8px;
      font-weight: 700;
      color: #222;
    }

    .signup-card p {
      font-size: 14px;
      margin-bottom: 28px;
      color: #666;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 8px;
      color: #333;
    }

    .form-group input {
      width: 100%;
      padding: 12px 14px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      background: #f9f9f9;
      color: #222;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .form-group input:focus {
      outline: none;
      border-color: #667eea;
      background: white;
      box-shadow: 0px 0px 0px 4px rgba(102, 126, 234, 0.1);
    }

    .error-message {
      color: #f44336;
      font-size: 12px;
      margin-top: 6px;
      display: block;
    }

    .signup-btn {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 10px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 10px;
    }

    .signup-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0px 10px 20px rgba(102, 126, 234, 0.3);
    }

    .login-text {
      margin-top: 20px;
      font-size: 14px;
      text-align: center;
      color: #666;
    }

    .login-text a {
      color: #667eea;
      font-weight: 600;
      text-decoration: none;
    }

    .login-text a:hover {
      color: #764ba2;
      text-decoration: underline;
    }

    .general-error {
      background: #ffecec;
      color: #f44336;
      padding: 12px;
      border-radius: 10px;
      font-size: 13px;
      margin-bottom: 15px;
      border: 1px solid #f44336;
    }
  </style>
</head>
<body>

  <div class="signup-container">
    <div class="signup-card">
      <h1>Create Account</h1>
      <p>Join us today and get started</p>

      <?php if (!empty($errors['general'])): ?>
        <div class="general-error">
          <?= htmlspecialchars($errors['general']); ?>
        </div>
      <?php endif; ?>

      <form action="signup_process.php" method="POST" novalidate>

        <div class="form-group">
          <label for="username">Username</label>
          <input
            type="text"
            id="username"
            name="username"
            placeholder="Choose a username"
            required
            minlength="3"
            maxlength="20"
            value="<?= htmlspecialchars($data['username'] ?? '') ?>"
          />
          <?php if (!empty($errors['username'])): ?>
            <div class="error-message"><?= htmlspecialchars($errors['username']); ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="your@email.com"
            required
            value="<?= htmlspecialchars($data['email'] ?? '') ?>"
          />
          <?php if (!empty($errors['email'])): ?>
            <div class="error-message"><?= htmlspecialchars($errors['email']); ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="At least 8 characters"
            required
            minlength="8"
          />
          <?php if (!empty($errors['password'])): ?>
            <div class="error-message"><?= htmlspecialchars($errors['password']); ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="confirm-password">Confirm Password</label>
          <input
            type="password"
            id="confirm-password"
            name="confirm_password"
            placeholder="Re-enter your password"
            required
          />
          <?php if (!empty($errors['confirm_password'])): ?>
            <div class="error-message"><?= htmlspecialchars($errors['confirm_password']); ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label style="display: flex; align-items: center; font-weight: 400; cursor: pointer;">
            <input
              type="checkbox"
              id="terms"
              name="terms"
              required
              style="width: 18px; height: 18px; margin-right: 8px;"
              <?= isset($data['terms']) ? 'checked' : '' ?>
            />
            I agree to the <a href="terms.html" style="color: #667eea; text-decoration: none;">Terms of Service</a>
          </label>

          <?php if (!empty($errors['terms'])): ?>
            <div class="error-message"><?= htmlspecialchars($errors['terms']); ?></div>
          <?php endif; ?>
        </div>

        <button class="signup-btn" type="submit">
          Create Account
        </button>

        <p class="login-text">
          Already have an account? <a href="login.php">Log in here</a>
        </p>
      </form>
    </div>
  </div>

  <?php
  // End output buffering - send all output at once
  ob_end_flush();
  ?>
</body>
</html>
