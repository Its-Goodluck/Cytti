<?php
require_once 'config.php';

// Only allow POST requests from M-Pesa
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Get the callback data
$callback_data = json_decode(file_get_contents('php://input'), true);

if (!$callback_data) {
    error_log("Invalid M-Pesa callback data");
    http_response_code(400);
    exit('Invalid data');
}

// Log the callback for debugging
error_log("M-Pesa Callback: " . json_encode($callback_data));

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed in callback: " . $e->getMessage());
    http_response_code(500);
    exit('Database error');
}

// Process the callback
if (isset($callback_data['Body']['stkCallback'])) {
    $stk_callback = $callback_data['Body']['stkCallback'];

    $merchant_request_id = $stk_callback['MerchantRequestID'];
    $checkout_request_id = $stk_callback['CheckoutRequestID'];
    $result_code = $stk_callback['ResultCode'];
    $result_desc = $stk_callback['ResultDesc'];

    // Find the payment record
    $stmt = $pdo->prepare("SELECT id, booking_id FROM payments WHERE merchant_request_id = ? AND checkout_request_id = ?");
    $stmt->execute([$merchant_request_id, $checkout_request_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($payment) {
        $payment_id = $payment['id'];
        $booking_id = $payment['booking_id'];

        if ($result_code == 0) {
            // Payment successful
            $callback_metadata = $stk_callback['CallbackMetadata']['Item'];

            $amount = null;
            $mpesa_receipt_number = null;
            $transaction_date = null;
            $phone_number = null;

            // Extract metadata
            foreach ($callback_metadata as $item) {
                switch ($item['Name']) {
                    case 'Amount':
                        $amount = $item['Value'];
                        break;
                    case 'MpesaReceiptNumber':
                        $mpesa_receipt_number = $item['Value'];
                        break;
                    case 'TransactionDate':
                        $transaction_date = $item['Value'];
                        break;
                    case 'PhoneNumber':
                        $phone_number = $item['Value'];
                        break;
                }
            }

            // Update payment record
            $stmt = $pdo->prepare("UPDATE payments SET status = 'completed', mpesa_receipt_number = ?, transaction_id = ?, response_code = ?, response_description = ? WHERE id = ?");
            $stmt->execute([
                $mpesa_receipt_number,
                $mpesa_receipt_number, // Using receipt number as transaction ID
                $result_code,
                $result_desc,
                $payment_id
            ]);

            // Update booking status
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
            $stmt->execute([$booking_id]);

            error_log("Payment completed successfully for booking ID: $booking_id, Receipt: $mpesa_receipt_number");

        } else {
            // Payment failed
            $stmt = $pdo->prepare("UPDATE payments SET status = 'failed', response_code = ?, response_description = ? WHERE id = ?");
            $stmt->execute([$result_code, $result_desc, $payment_id]);

            error_log("Payment failed for booking ID: $booking_id, Result: $result_desc");
        }
    } else {
        error_log("Payment record not found for MerchantRequestID: $merchant_request_id, CheckoutRequestID: $checkout_request_id");
    }
} else {
    error_log("Invalid M-Pesa callback structure");
}

// Always respond with success to M-Pesa
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback received successfully']);
?>
