<?php
// get_order_status.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT status, delivery_lat, delivery_lng, restaurant_lat, restaurant_lng, rider_lat, rider_lng
                          FROM orders 
                          WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if ($order) {
        echo json_encode([
            'success' => true,
            'status' => $order['status'],
            'delivery_lat' => (float)$order['delivery_lat'],
            'delivery_lng' => (float)$order['delivery_lng'],
            'restaurant_lat' => (float)$order['restaurant_lat'],
            'restaurant_lng' => (float)$order['restaurant_lng'],
            'rider_lat' => $order['rider_lat'] ? (float)$order['rider_lat'] : null,
            'rider_lng' => $order['rider_lng'] ? (float)$order['rider_lng'] : null
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
