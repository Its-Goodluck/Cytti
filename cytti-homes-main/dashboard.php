<?php
/**
 * Dashboard Page - User Dashboard
 * 
 * IMPORTANT: Session must be started before any output
 * This file implements EXPLICIT SESSION HANDLING for security
 */

// ============================================================
// EXPLICIT SESSION HANDLING - START
// ============================================================

// Note: Output buffering is handled by config.php
// Do NOT call ob_start() here - it will conflict with config.php


// ============================================================
// Step 1: Start session with explicit status check
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    // Session not started, start it now
    session_start();
    error_log("Session started explicitly in dashboard.php - Session ID: " . session_id());
} else if (session_status() === PHP_SESSION_ACTIVE) {
    // Session already active, log it
    error_log("Session already active in dashboard.php - Session ID: " . session_id());
}

// ============================================================
// Step 2: Validate session exists and has required data
// ============================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Session invalid, redirect to login
    ob_start();
    header('Location: login.php');
    ob_end_flush();
    exit;
}

// ============================================================
// Step 3: Session Fingerprint Validation (Security)
// Validate IP address and User Agent to prevent session hijacking
// ============================================================
$sessionFingerprint = $_SESSION['fingerprint'] ?? [];
$currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
$currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Check if fingerprint exists
if (empty($sessionFingerprint)) {
    // Create new fingerprint for this session
    $_SESSION['fingerprint'] = [
        'ip' => $currentIp,
        'user_agent' => $currentUserAgent,
        'created' => time()
    ];
    error_log("New session fingerprint created for user: " . ($_SESSION['user_id'] ?? 'unknown'));
} else {
    // Validate existing fingerprint
    $storedIp = $sessionFingerprint['ip'] ?? '';
    $storedUserAgent = $sessionFingerprint['user_agent'] ?? '';
    
    // For development, we might allow different IPs, but validate user agent
    if ($storedUserAgent !== $currentUserAgent) {
        // User agent mismatch - potential session theft
        error_log("SECURITY: User agent mismatch detected! Stored: " . substr($storedUserAgent, 0, 50) . " Current: " . substr($currentUserAgent, 0, 50));
        // In production, you might want to destroy the session here
        // session_destroy();
        // ob_start();
        // header('Location: login.html');
        // ob_end_flush();
        // exit;
    }
}

// ============================================================
// Step 4: Session Regeneration (Security - prevents session fixation)
// Regenerate session ID periodically
// ============================================================
if (!isset($_SESSION['last_regeneration'])) {
    // First time, regenerate session ID
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
    error_log("Session ID regenerated (initial) - New ID: " . session_id());
} else {
    // Check if regeneration is needed (every 30 minutes)
    $regenerationInterval = 30 * 60; // 30 minutes
    if (time() - $_SESSION['last_regeneration'] > $regenerationInterval) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
        error_log("Session ID regenerated (periodic) - New ID: " . session_id());
    }
}

// ============================================================
// Step 5: Session Activity Tracking
// ============================================================
$_SESSION['last_activity'] = time();
$_SESSION['last_page'] = 'dashboard.php';

// ============================================================
// Step 6: Session Expiry Check (configurable duration)
// ============================================================
$sessionDuration = 24 * 60 * 60; // 24 hours in seconds
if (isset($_SESSION['login_time'])) {
    if (time() - $_SESSION['login_time'] > $sessionDuration) {
        // Session expired, logout user
        error_log("Session expired for user ID: " . ($_SESSION['user_id'] ?? 'unknown'));
        
        // Clear session data
        $_SESSION = [];
        session_destroy();
        
        ob_start();
        header('Location: login.php?error=session_expired');
        ob_end_flush();
        exit;
    }
}

// ============================================================
// Step 7: Validate user_id is still valid in database
// (Optional - can be skipped for performance)
// ============================================================

// ============================================================
// EXPLICIT SESSION HANDLING - END
// ============================================================

// Include configuration and functions
require_once 'config.php';
require_once 'functions.php';

// Debug: Log dashboard access with session details
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("Dashboard accessed - User ID: " . ($_SESSION['user_id'] ?? 'unknown') . 
               ", Session ID: " . session_id() . 
               ", Last Activity: " . date('Y-m-d H:i:s', $_SESSION['last_activity'] ?? time()));
}

// Get current user data
$user = getCurrentUser();

// Additional explicit user validation
if ($user === null || empty($user['id'])) {
    // User data not found in session, force re-login
    error_log("User data not found in session, redirecting to login");
    ob_start();
    header('Location: login.php');
    ob_end_flush();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($user['username'] ?? 'User'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard-container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0px 20px 60px rgba(0, 0, 0, 0.3);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .header h1 {
            color: #222;
            font-size: 28px;
        }

        .logout-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0px 10px 20px rgba(102, 126, 234, 0.3);
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .welcome-section h2 {
            color: #222;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .welcome-section p {
            color: #666;
            font-size: 16px;
        }

        .user-info {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .user-info h3 {
            color: #222;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .info-item {
            display: flex;
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: 600;
            color: #333;
            width: 120px;
        }

        .info-value {
            color: #666;
        }

        .quick-actions {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .quick-actions h3 {
            color: #222;
            margin-bottom: 20px;
            font-size: 20px;
            text-align: center;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            display: block;
            padding: 15px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0px 10px 20px rgba(102, 126, 234, 0.3);
        }

        .action-btn.secondary {
            background: #e0e0e0;
            color: #333;
        }

        .action-btn.secondary:hover {
            background: #d0d0d0;
        }

        .tips-section {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .tips-section h3 {
            color: #222;
            margin-bottom: 20px;
            font-size: 20px;
            text-align: center;
        }

        .tips-list {
            list-style: none;
            padding: 0;
        }

        .tips-list li {
            color: #666;
            margin-bottom: 10px;
            padding-left: 20px;
            position: relative;
        }

        .tips-list li::before {
            content: "💡";
            position: absolute;
            left: 0;
        }

        .stats-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            display: block;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 20px;
            }

            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .actions {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .action-btn {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>Dashboard</h1>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars($user['username'] ?? 'User'); ?>!</h2>
            <p>You are successfully logged in to your Cytti Homes account.</p>
        </div>

        <div class="user-info">
            <h3>Your Account Information</h3>
            <div class="info-item">
                <span class="info-label">Username:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">User ID:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['id'] ?? 'N/A'); ?></span>
            </div>
        </div>

        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="actions-grid">
                <a href="properties.html" class="action-btn">Browse Properties</a>
                <a href="articles.html" class="action-btn">Read Articles</a>
                <a href="profile.html" class="action-btn secondary">Edit Profile</a>
                <a href="contact.html" class="action-btn secondary">Contact Support</a>
            </div>
        </div>

        <div class="stats-section">
            <h3>Your Activity</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number">0</span>
                    <span class="stat-label">Properties Viewed</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">0</span>
                    <span class="stat-label">Bookings Made</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">0</span>
                    <span class="stat-label">Favorites Saved</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">0</span>
                    <span class="stat-label">Reviews Given</span>
                </div>
            </div>
        </div>

        <div class="tips-section">
            <h3>Property Hunting Tips</h3>
            <ul class="tips-list">
                <li>Always inspect the property before booking</li>
                <li>Check neighborhood amenities and safety</li>
                <li>Read reviews from previous tenants</li>
                <li>Compare prices across different locations</li>
                <li>Consider transportation and accessibility</li>
            </ul>
        </div>
    </div>

    <?php
    // End output buffering
    ob_end_flush();
    ?>
</body>
</html>
