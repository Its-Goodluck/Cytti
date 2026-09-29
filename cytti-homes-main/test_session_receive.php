<?php
/**
 * Test Session Receive - Verify session cookie is received after redirect
 */

require_once 'config.php';

echo "<h1>Session Receive Test</h1>";

// Check session status
echo "<p>Session status: " . session_status() . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";

// Try to read the test session variable
if (isset($_SESSION['test_value'])) {
    echo "<p style='color: green;'>SUCCESS! Session variable received: " . $_SESSION['test_value'] . "</p>";
    echo "<p>Time set: " . date('Y-m-d H:i:s', $_SESSION['test_time']) . "</p>";
} else {
    echo "<p style='color: red;'>FAILED! Session variable NOT received.</p>";
    echo "<p>This indicates the session cookie is not being passed properly.</p>";
}

// Show all session variables
echo "<p>All session variables:</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p><a href='test_session_fix.php'>Back to test_session_fix.php</a></p>";
?>
