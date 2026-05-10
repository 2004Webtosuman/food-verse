<?php
// delivery/update_location.php — AJAX endpoint for rider GPS updates
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_delivery()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
$lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if (!$lat || !$lng || !$order_id) {
    echo json_encode(['success' => false, 'message' => 'Missing lat, lng, or order_id']);
    exit;
}

try {
    // Update rider position on the assigned order
    $stmt = $pdo->prepare("UPDATE orders SET rider_lat = ?, rider_lng = ? WHERE id = ? AND delivery_user_id = ?");
    $stmt->execute([$lat, $lng, $order_id, $user_id]);

    // Also update the rider's own location in users table
    $pdo->prepare("UPDATE users SET latitude = ?, longitude = ? WHERE id = ?")->execute([$lat, $lng, $user_id]);

    echo json_encode(['success' => true, 'message' => 'Location updated']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
