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
$order_id = (int)($_GET['order_id'] ?? 0);
$last_id = (int)($_GET['last_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT m.*, u.full_name as sender_name 
                          FROM messages m 
                          JOIN users u ON m.sender_id = u.id 
                          WHERE m.order_id = ? AND m.id > ? 
                          ORDER BY m.created_at ASC");
    $stmt->execute([$order_id, $last_id]);
    $messages = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
