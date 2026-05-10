<?php
// actions/esewa_success.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isset($_GET['data'])) {
    die("Invalid response from eSewa.");
}

$decoded_data = base64_decode($_GET['data']);
$response = json_decode($decoded_data, true);

if (!$response || $response['status'] !== 'COMPLETE') {
    $_SESSION['flash_message'] = "Payment failed or was cancelled.";
    redirect('../customer/checkout.php?step=2');
}

// Verify Signature (Advanced security)
$secret_key = "8gBm/:&EnhH.1/q";
$signed_field_names = explode(',', $response['signed_field_names']);
$signature_data = [];
foreach ($signed_field_names as $field) {
    if (isset($response[$field])) {
        $signature_data[] = "$field=" . $response[$field];
    }
}
$signature_string = implode(',', $signature_data);
$hash = hash_hmac('sha256', $signature_string, $secret_key, true);
$expected_signature = base64_encode($hash);

if ($response['signature'] !== $expected_signature) {
    die("Security verification failed. Invalid signature.");
}

// Payment successful!
$transaction_uuid = $response['transaction_uuid'];

try {
    // Update order status AND payment status
    $stmt = $pdo->prepare("UPDATE orders SET status = 'paid', payment_status = 'paid' WHERE transaction_uuid = ?");
    $stmt->execute([$transaction_uuid]);
    
    // Get Order ID
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE transaction_uuid = ?");
    $stmt->execute([$transaction_uuid]);
    $order_id = $stmt->fetchColumn();
    
    // Clear Cart
    $_SESSION['cart'] = [];

    // Send Emails
    require_once '../includes/mail_helper.php';
    triggerOrderEmail($order_id, 'new_order');
    
    $_SESSION['flash_message'] = "Payment successful! Your order #$order_id has been placed.";
    redirect('../customer/order_success.php?id=' . $order_id);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
