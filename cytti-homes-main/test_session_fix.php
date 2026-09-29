<?php
/**
 * Test Session Fix - Verify session cookie works after redirect
 */

require_once 'config.php';

echo "<h1>Session Fix Test</h1>";
echo "<p>Testing session configuration...</p>";

// Check session status
echo "<p>Session status: " . session_status() . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";

// Set a test session variable
$_SESSION['test_value'] = 'Hello from test!';
$_SESSION['test_time'] = time();

echo "<p>Test session variable set: " . $_SESSION['test_value'] . "</p>";

// Check cookie parameters
echo "<p>Session cookie params:</p>";
echo "<ul>";
echo "<li>Name: " . session_name() . "</li>";
echo "<li>SameSite: " . ini_get('session.cookie_samesite') . "</li>";
echo "<li>HttpOnly: " . ini_get('session.cookie_httponly') . "</li>";
echo "<li>Path: " . ini_get('session.cookie_path') . "</li>";
echo "</ul>";

echo "<p><strong>Now test the redirect by clicking this link:</strong></p>";
echo "<p><a href='test_session_receive.php'>Go to test_session_receive.php</a></p>";
?>
