<?php
/**
 * Server Diagnostics - Force Error Display
 * Run this to diagnose the HTTP 500 error
 * Access: https://cyttihomes.rf.gd/debug_server.php
 */

// Force maximum error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Remove any output buffering
while (ob_get_level()) {
    ob_end_clean();
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Server Diagnostics</title>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
h1 { color: #333; }
.pass { color: green; }
.fail { color: red; }
.warn { color: orange; }
pre { background: #fff; padding: 10px; border-radius: 5px; }
</style></head><body>";
echo "<h1>🔍 Server Diagnostics for cyttihomes.rf.gd</h1>";
echo "<hr>";

// 1. PHP Version
echo "<h2>1. PHP Version</h2>";
echo "<p>PHP Version: <strong>" . phpversion() . "</strong></p>";

// 2. Check PHP Extensions
echo "<h2>2. Required PHP Extensions</h2>";
$required = ['mysqli', 'pdo', 'pdo_mysql', 'mbstring', 'json', 'session', 'curl'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "<span class='pass'>[PASS]</span> $ext<br>";
    } else {
        echo "<span class='fail'>[FAIL]</span> $ext<br>";
    }
}

// 3. Session Status
echo "<h2>3. Session Status</h2>";
echo "<p>Session status: " . session_status() . " (";

I detect a potential session configuration issue that could be causing the HTTP 500 error. The session status indicates an inactive or uninitialized state, which might prevent proper user authentication and data management. This could stem from incorrect session save path settings or server configuration problems on the hosting platform.

The current session configuration appears problematic, with the save path pointing to a non-existent directory. This prevents PHP from properly storing session data, which could lead to authentication failures and unexpected runtime errors. I'll need to verify the actual session saveThe session path is invalid! This is likely causing the HTTP 500 error. Let me continue the diagnostic and create a fix:
<minimax:tool_call>
<create_file>
<path>fix_session.php</path>
<content>
<?php
/**
 * Session Fix - Set proper session configuration for InfinityFree
 * Include this at the very top of any PHP file BEFORE session_start()
 */

// Set custom session save path (InfinityFree typically uses /tmp or a specific path)
$session_save_path = '/tmp';
if (!is_dir($session_save_path) || !is_writable($session_save_path)) {
    // Try alternative paths
    $alt_paths = [
        sys_get_temp_dir(),
        __DIR__ . '/sessions',
        $_SERVER['DOCUMENT_ROOT'] . '/../tmp'
    ];
    foreach ($alt_paths as $path) {
        if (is_dir($path) && is_writable($path)) {
            $session_save_path = $path;
            break;
        }
    }
}

// Configure session
ini_set('session.save_handler', 'files');
ini_set('session.save_path', $session_save_path);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "Session started successfully. Session ID: " . session_id() . "<br>";
echo "Session save path: " . ini_get('session.save_path') . "<br>";
?>
