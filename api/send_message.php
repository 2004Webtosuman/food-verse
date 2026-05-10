<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = (int)($_POST['order_id'] ?? 0);
$message = sanitize($_POST['message'] ?? '');
$receiver_id = (int)($_POST['receiver_id'] ?? 0);

if ($order_id <= 0 || empty($message) || $receiver_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (order_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$order_id, $user_id, $receiver_id, $message]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
