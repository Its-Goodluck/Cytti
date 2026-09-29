<?php
require_once 'config.php';
require_once 'functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Only allow AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Get JSON input for AJAX requests
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST; // Fallback for form data
}

// Validate required fields
$required_fields = ['firstName', 'lastName', 'email', 'phone', 'country', 'checkIn', 'checkOut', 'paymentMethod', 'propertyId', 'nights', 'totalPrice'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        echo json_encode(['success' => false, 'message' => 'Missing required field: ' . $field]);
        exit;
    }
}

// Validate email
if (!validateEmail($input['email'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate payment method specific fields
if ($input['paymentMethod'] === 'mpesa' && (!isset($input['mpesaPhone']) || empty(trim($input['mpesaPhone'])))) {
    echo json_encode(['success' => false, 'message' => 'M-Pesa phone number is required']);
    exit;
}

if ($input['paymentMethod'] === 'card') {
    $card_fields = ['cardNumber', 'cardExpiry', 'cardCVC'];
    foreach ($card_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            echo json_encode(['success' => false, 'message' => 'Missing required card field: ' . $field]);
            exit;
        }
    }
}

// Validate dates
$checkIn = date('Y-m-d', strtotime($input['checkIn']));
$checkOut = date('Y-m-d', strtotime($input['checkOut']));

if ($checkIn >= $checkOut) {
    echo json_encode(['success' => false, 'message' => 'Check-out date must be after check-in date']);
    exit;
}

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if user exists or create guest user
$user_id = null;
$email = sanitizeInput($input['email']);

// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $user_id = $user['id'];
} else {
    // Create guest user (you might want to modify this based on your requirements)
    $username = sanitizeInput($input['firstName'] . ' ' . $input['lastName']);
    $password_hash = password_hash(uniqid(), PASSWORD_DEFAULT); // Temporary password

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $password_hash]);
    $user_id = $pdo->lastInsertId();
}

// Create booking
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, property_id, check_in, check_out, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $user_id,
        $input['propertyId'],
        $checkIn,
        $checkOut,
        $input['totalPrice']
    ]);

    $booking_id = $pdo->lastInsertId();

    // Create payment record
    $payment_method = $input['paymentMethod'];
    $phone_number = ($payment_method === 'mpesa') ? sanitizeInput($input['mpesaPhone']) : null;

    $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method, phone_number, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $booking_id,
        $input['totalPrice'],
        $payment_method,
        $phone_number
    ]);

    $payment_id = $pdo->lastInsertId();

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Booking creation failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to create booking']);
    exit;
}

// Handle M-Pesa payment
if ($payment_method === 'mpesa') {
    // Check if M-Pesa credentials are configured
    $consumer_key = 'your_consumer_key_here'; // Replace with actual credentials
    $consumer_secret = 'your_consumer_secret_here'; // Replace with actual credentials

    if ($consumer_key === 'your_consumer_key_here' || $consumer_secret === 'your_consumer_secret_here') {
        // M-Pesa not configured, treat as pending payment
        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully. M-Pesa payment will be processed once credentials are configured.',
            'bookingId' => $booking_id,
            'paymentId' => $payment_id
        ]);
    } else {
        $mpesa_result = initiateMpesaPayment($booking_id, $payment_id, $input['totalPrice'], $phone_number, $pdo);

        if ($mpesa_result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Booking created. Please check your phone for M-Pesa payment prompt.',
                'bookingId' => $booking_id,
                'paymentId' => $payment_id,
                'checkoutRequestId' => $mpesa_result['checkoutRequestId'],
                'merchantRequestId' => $mpesa_result['merchantRequestId']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Booking created but payment initiation failed: ' . $mpesa_result['message']
            ]);
        }
    }
} else {
    // For card/bank payments, you would integrate with respective payment gateways here
    echo json_encode([
        'success' => true,
        'message' => 'Booking created successfully. Payment processing will be handled separately.',
        'bookingId' => $booking_id,
        'paymentId' => $payment_id
    ]);
}

/**
 * Initiate M-Pesa STK Push payment
 */
function initiateMpesaPayment($booking_id, $payment_id, $amount, $phone_number, $pdo) {
    // M-Pesa API credentials (you should store these securely)
    $consumer_key = 'your_consumer_key_here'; // Replace with actual credentials
    $consumer_secret = 'your_consumer_secret_here'; // Replace with actual credentials
    $shortcode = '174379'; // Replace with your shortcode
    $passkey = 'your_passkey_here'; // Replace with actual passkey

    // Get access token
    $access_token = getMpesaAccessToken($consumer_key, $consumer_secret);
    if (!$access_token) {
        return ['success' => false, 'message' => 'Failed to get M-Pesa access token'];
    }

    // Format phone number (remove + and ensure it starts with 254)
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
    if (strpos($phone_number, '254') !== 0) {
        $phone_number = '254' . substr($phone_number, -9);
    }

    // Generate timestamp and password
    $timestamp = date('YmdHis');
    $password = base64_encode($shortcode . $passkey . $timestamp);

    // Prepare STK Push request
    $stk_push_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'; // Use production URL for live

    $curl_post_data = [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => round($amount),
        'PartyA' => $phone_number,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phone_number,
        'CallBackURL' => SITE_URL . '/mpesa_callback.php', // You'll need to create this
        'AccountReference' => 'CYTTI-BOOKING-' . $booking_id,
        'TransactionDesc' => 'Cytti Homes Property Booking'
    ];

    $data_string = json_encode($curl_post_data);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $stk_push_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Remove in production

    $curl_response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        error_log("M-Pesa STK Push cURL error: " . $error);
        return ['success' => false, 'message' => 'Payment request failed'];
    }

    $response = json_decode($curl_response, true);

    if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
        // Update payment record with M-Pesa details
        $stmt = $pdo->prepare("UPDATE payments SET merchant_request_id = ?, checkout_request_id = ?, status = 'processing' WHERE id = ?");
        $stmt->execute([
            $response['MerchantRequestID'],
            $response['CheckoutRequestID'],
            $payment_id
        ]);

        return [
            'success' => true,
            'checkoutRequestId' => $response['CheckoutRequestID'],
            'merchantRequestId' => $response['MerchantRequestID']
        ];
    } else {
        $error_message = isset($response['ResponseDescription']) ? $response['ResponseDescription'] : 'Unknown error';

        // Update payment record with error
        $stmt = $pdo->prepare("UPDATE payments SET status = 'failed', response_code = ?, response_description = ? WHERE id = ?");
        $stmt->execute([
            $response['ResponseCode'] ?? 'UNKNOWN',
            $error_message,
            $payment_id
        ]);

        return ['success' => false, 'message' => $error_message];
    }
}

/**
 * Get M-Pesa access token
 */
function getMpesaAccessToken($consumer_key, $consumer_secret) {
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'; // Use production URL for live

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
    ]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Remove in production

    $curl_response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        error_log("M-Pesa access token cURL error: " . $error);
        return false;
    }

    $response = json_decode($curl_response, true);
    return isset($response['access_token']) ? $response['access_token'] : false;
}
?>
