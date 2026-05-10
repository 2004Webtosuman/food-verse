<?php
// delivery/api/get_notifications.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_delivery()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch unread notifications for this rider
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Automated background expansion worker simulation
// Look for any confirmed, unassigned orders that haven't been polled in the last 15 seconds
$b_stmt = $pdo->query("
    SELECT ob.order_id 
    FROM order_broadcasts ob
    JOIN orders o ON ob.order_id = o.id
    WHERE o.status = 'confirmed' 
    AND o.delivery_user_id IS NULL
    AND ob.last_polled_at < DATE_SUB(NOW(), INTERVAL 15 SECOND)
");
$pending_broadcasts = $b_stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($pending_broadcasts as $o_id) {
    assign_nearest_rider($o_id, true);
}

echo json_encode([
    'success' => true,
    'count' => count($notifications),
    'notifications' => $notifications
]);
