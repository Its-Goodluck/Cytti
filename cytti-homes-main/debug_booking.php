<?php
require_once 'config.php';
require_once 'functions.php';

echo "=== Booking Creation Debug ===\n\n";

// Test database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connection successful\n";
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Check if tables exist
$tables = ['users', 'properties', 'bookings', 'payments'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✓ Table '$table' exists with $count records\n";
    } catch (Exception $e) {
        echo "✗ Table '$table' error: " . $e->getMessage() . "\n";
    }
}

// Check recent bookings
echo "\n=== Recent Bookings ===\n";
try {
    $stmt = $pdo->query("SELECT b.*, u.email, p.title FROM bookings b JOIN users u ON b.user_id = u.id JOIN properties p ON b.property_id = p.id ORDER BY b.created_at DESC LIMIT 5");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($bookings)) {
        echo "No bookings found\n";
    } else {
        foreach ($bookings as $booking) {
            echo "ID: {$booking['id']}, User: {$booking['email']}, Property: {$booking['title']}, Status: {$booking['status']}, Created: {$booking['created_at']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error fetching bookings: " . $e->getMessage() . "\n";
}

// Check recent payments
echo "\n=== Recent Payments ===\n";
try {
    $stmt = $pdo->query("SELECT p.*, b.check_in, b.check_out FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id ORDER BY p.created_at DESC LIMIT 5");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($payments)) {
        echo "No payments found\n";
    } else {
        foreach ($payments as $payment) {
            echo "ID: {$payment['id']}, Booking ID: {$payment['booking_id']}, Amount: {$payment['amount']}, Method: {$payment['payment_method']}, Status: {$payment['status']}, Created: {$payment['created_at']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error fetching payments: " . $e->getMessage() . "\n";
}

// Check for any failed transactions
echo "\n=== Failed Transactions ===\n";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE status = 'failed'");
    $stmt->execute();
    $failed_count = $stmt->fetchColumn();
    echo "Failed payments: $failed_count\n";

    if ($failed_count > 0) {
        $stmt = $pdo->query("SELECT * FROM payments WHERE status = 'failed' ORDER BY created_at DESC LIMIT 3");
        $failed_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($failed_payments as $payment) {
            echo "Failed Payment ID: {$payment['id']}, Response: {$payment['response_description']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error checking failed transactions: " . $e->getMessage() . "\n";
}

// Check PHP error log
echo "\n=== PHP Error Log Check ===\n";
$error_log_path = ini_get('error_log');
if ($error_log_path && file_exists($error_log_path)) {
    $lines = file($error_log_path);
    $recent_errors = array_slice($lines, -10); // Last 10 lines
    echo "Recent PHP errors:\n";
    foreach ($recent_errors as $error) {
        echo $error;
    }
} else {
    echo "No PHP error log found at: $error_log_path\n";
}

echo "\n=== Configuration Check ===\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "SITE_URL: " . SITE_URL . "\n";
echo "M-Pesa Callback URL: " . SITE_URL . "/mpesa_callback.php\n";

echo "\n=== Test Data Validation ===\n";
// Test with sample data
$test_data = [
    'firstName' => 'John',
    'lastName' => 'Doe',
    'email' => 'john.doe@example.com',
    'phone' => '+254712345678',
    'country' => 'Kenya',
    'checkIn' => '2024-02-01',
    'checkOut' => '2024-02-03',
    'paymentMethod' => 'mpesa',
    'mpesaPhone' => '0712345678',
    'propertyId' => '1',
    'nights' => '2',
    'totalPrice' => '30000.00'
];

echo "Testing validation with sample data...\n";
$errors = [];

// Check required fields
$required_fields = ['firstName', 'lastName', 'email', 'phone', 'country', 'checkIn', 'checkOut', 'paymentMethod', 'propertyId', 'nights', 'totalPrice'];
foreach ($required_fields as $field) {
    if (!isset($test_data[$field]) || empty(trim($test_data[$field]))) {
        $errors[] = "Missing required field: $field";
    }
}

// Validate email
if (!validateEmail($test_data['email'])) {
    $errors[] = 'Invalid email address';
}

// Validate payment method specific fields
if ($test_data['paymentMethod'] === 'mpesa' && (!isset($test_data['mpesaPhone']) || empty(trim($test_data['mpesaPhone'])))) {
    $errors[] = 'M-Pesa phone number is required';
}

// Validate dates
$checkIn = date('Y-m-d', strtotime($test_data['checkIn']));
$checkOut = date('Y-m-d', strtotime($test_data['checkOut']));

if ($checkIn >= $checkOut) {
    $errors[] = 'Check-out date must be after check-in date';
}

if (empty($errors)) {
    echo "✓ Sample data validation passed\n";
} else {
    echo "✗ Validation errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n=== End Debug ===\n";
?>
