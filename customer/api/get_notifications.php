<?php
// customer/api/get_notifications.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch unread notifications for this customer
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'count' => count($notifications),
    'notifications' => $notifications
]);
