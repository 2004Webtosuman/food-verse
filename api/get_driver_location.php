<?php
// api/get_driver_location.php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Missing order_id']);
    exit;
}

try {
    // Join with drivers table if possible, fallback to orders.rider_lat
    $stmt = $pdo->prepare("SELECT o.rider_lat, o.rider_lng, o.delivery_user_id, o.status,
                                  d.lat as driver_lat, d.lng as driver_lng, d.last_updated
                           FROM orders o 
                           LEFT JOIN drivers d ON o.delivery_user_id = d.user_id 
                           WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $data = $stmt->fetch();

    if ($data) {
        $lat = $data['driver_lat'] ?? $data['rider_lat'];
        $lng = $data['driver_lng'] ?? $data['rider_lng'];
        
        echo json_encode([
            'success' => true,
            'lat' => $lat,
            'lng' => $lng,
            'status' => $data['status'],
            'last_updated' => $data['last_updated'] ?? null
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
