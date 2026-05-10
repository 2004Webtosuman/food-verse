<?php
// api/update_driver_location.php
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
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;

if (!$lat || !$lng) {
    echo json_encode(['success' => false, 'message' => 'Missing lat or lng']);
    exit;
}

try {
    // 1. Update/Insert drivers table
    $stmt = $pdo->prepare("INSERT INTO drivers (user_id, lat, lng, last_updated) VALUES (?, ?, ?, NOW()) 
                           ON DUPLICATE KEY UPDATE lat = VALUES(lat), lng = VALUES(lng), last_updated = NOW()");
    $stmt->execute([$user_id, $lat, $lng]);

    // 2. Optionally, keep users table in sync 
    $pdo->prepare("UPDATE users SET latitude = ?, longitude = ? WHERE id = ?")->execute([$lat, $lng, $user_id]);

    // 3. If tied to an order, update order tracker
    if ($order_id) {
        $pdo->prepare("UPDATE orders SET rider_lat = ?, rider_lng = ? WHERE id = ? AND delivery_user_id = ?")
            ->execute([$lat, $lng, $order_id, $user_id]);
    }

    echo json_encode(['success' => true, 'message' => 'Location integrated']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
