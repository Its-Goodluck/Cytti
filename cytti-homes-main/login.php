<?php
/**
 * Login Page - User Login Form
 * 
 * FIXED: Session is now handled by config.php
 */

// Include configuration - this handles session and output buffering
require_once 'config.php';
require_once 'functions.php';

// Get any errors from session
$errors = $_SESSION['login_errors'] ?? [];
$email = $_SESSION['login_email'] ?? '';
$signupSuccess = $_SESSION['signup_success'] ?? '';

// Clear the session variables after retrieving them
unset($_SESSION['login_errors']);
unset($_SESSION['login_email']);
unset($_SESSION['signup_success']);

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

// Debug: Log if there were errors
if (defined('DEBUG_MODE') && DEBUG_MODE && !empty($errors)) {
    error_log("Login page loaded with errors: " . print_r($errors, true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Access Your Account</title>
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

    /* Animated background pattern */
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

    .login-container {
      position: relative;
      z-index: 2;
      width: 100%;
      max-width: 420px;
      padding: 20px;
    }

    .login-card {
      padding: 40px;
      border-radius: 20px;
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0px 20px 60px rgba(0, 0, 0, 0.3);
      color: #222;
      animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .login-card h1 {
      font-size: 28px;
      margin-bottom: 8px;
      font-weight: 700;
      color: #222;
    }

    .login-card p {
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

    .form-group input::placeholder {
      color: #999;
    }

    .options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 13px;
      margin: 20px 0 25px 0;
      flex-wrap: wrap;
      gap: 10px;
    }

    .options label {
      display: flex;
      align-items: center;
      margin: 0;
      cursor: pointer;
      user-select: none;
      color: #333;
      font-weight: 400;
    }

    .options label input[type="checkbox"] {
      width: 18px;
      height: 18px;
      margin-right: 6px;
      cursor: pointer;
      accent-color: #667eea;
    }

    .options a {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .options a:hover {
      color: #764ba2;
      text-decoration: underline;
    }

    .login-btn {
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
    }

    .login-btn:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0px 10px 20px rgba(102, 126, 234, 0.3);
    }

    .login-btn:active:not(:disabled) {
      transform: translateY(0);
    }

    .login-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .signup-text {
      margin-top: 20px;
      font-size: 14px;
      text-align: center;
      color: #666;
    }

    .signup-text a {
      color: #667eea;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .signup-text a:hover {
      color: #764ba2;
      text-decoration: underline;
    }

    .error-message {
      color: #f44336;
      font-size: 12px;
      margin-top: 6px;
      display: block;
      animation: shake 0.3s ease;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .divider {
      margin: 25px 0 20px 0;
      display: flex;
      align-items: center;
      color: #999;
      font-size: 12px;
    }

    .divider::before,
    .divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background: #e0e0e0;
    }

    .divider::before {
      margin-right: 10px;
    }

    .divider::after {
      margin-left: 10px;
    }

    .footer-text {
      font-size: 12px;
      color: #999;
      margin-top: 20px;
      text-align: center;
    }

    .footer-text a {
      color: #667eea;
      text-decoration: none;
    }

    .footer-text a:hover {
      text-decoration: underline;
    }

    @media (max-width: 480px) {
      .login-card {
        padding: 30px 20px;
      }

      .login-card h1 {
        font-size: 24px;
      }

      .options {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="login-card">
      <h1>Welcome Back</h1>
      <p>Log in to access your account</p>

      <?php if (!empty($signupSuccess)): ?>
        <div style="margin-bottom: 20px; padding: 12px; border-radius: 10px; background: #e8f5e9; color: #2e7d32; border: 1px solid #2e7d32; font-size: 13px;">
          <?= htmlspecialchars($signupSuccess); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors['general'])): ?>
        <div class="error-message" style="margin-bottom: 20px;"><?= htmlspecialchars($errors['general']); ?></div>
      <?php endif; ?>

      <form action="login_process.php" method="POST" novalidate>
        <!-- Email Field -->
        <div class="form-group">
          <label for="email">Email Address</label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="your@email.com"
            required
            value="<?php echo htmlspecialchars($email); ?>"
          />
          <?php if (isset($errors['email'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
          <?php endif; ?>
        </div>

        <!-- Password Field -->
        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            required
          />
          <?php if (isset($errors['password'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
          <?php endif; ?>
        </div>

        <!-- Options: Remember me & Forgot password -->
        <div class="options">
          <label>
            <input type="checkbox" id="remember-me" name="remember_me" />
            Remember me
          </label>
          <a href="forgot-password.html">Forgot password?</a>
        </div>

        <button class="login-btn" type="submit">
          Log In
        </button>

        <div class="divider">or</div>

        <p class="signup-text">
          Don't have an account? <a href="signup.php">Create one</a>
        </p>

        <div class="footer-text">
          By logging in, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
        </div>
      </form>
    </div>
  </div>

  <?php
  // End output buffering - send all output at once
  ob_end_flush();
  ?>

</body>
</html>
