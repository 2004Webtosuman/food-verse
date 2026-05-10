<?php
// api/voice_link.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';
$order_id = (int)($_REQUEST['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

try {
    if ($action === 'initiate') {
        $receiver_id = (int)($_POST['receiver_id'] ?? 0);
        // FORCE terminate any existing calls for this order to prevent loops
        $pdo->prepare("UPDATE calls SET status = 'ended' WHERE order_id = ? AND status != 'ended'")->execute([$order_id]);
        
        $stmt = $pdo->prepare("INSERT INTO calls (order_id, caller_id, receiver_id, status) VALUES (?, ?, ?, 'ringing')");
        $stmt->execute([$order_id, $user_id, $receiver_id]);
        echo json_encode(['success' => true, 'call_id' => $pdo->lastInsertId()]);
    } 
    elseif ($action === 'check') {
        // Find latest active call for this order
        $stmt = $pdo->prepare("SELECT c.*, u.full_name as caller_name 
                               FROM calls c 
                               JOIN users u ON c.caller_id = u.id 
                               WHERE c.order_id = ? AND (c.receiver_id = ? OR c.caller_id = ?)
                               ORDER BY c.created_at DESC LIMIT 1");
        $stmt->execute([$order_id, $user_id, $user_id]);
        $call = $stmt->fetch();
        
        if (!$call) {
            echo json_encode(['success' => true, 'call' => null]);
            exit;
        }

        // Determine if it's incoming or outgoing for ME
        $isIncoming = ($call['receiver_id'] == $user_id);
        
        echo json_encode([
            'success' => true, 
            'call' => $call,
            'is_incoming' => $isIncoming,
            'debug_user_id' => $user_id,
            'debug_target_receiver' => $call['receiver_id'],
            'debug_match' => ($call['receiver_id'] == $user_id)
        ]);
    }
    elseif ($action === 'update') {
        $call_id = (int)($_POST['call_id'] ?? 0);
        $new_status = $_POST['status'] ?? ''; // accepted, declined, ended
        
        if (!in_array($new_status, ['accepted', 'declined', 'ended'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            exit;
        }
        
        // When ending or declining, terminate everything for this order
        if ($new_status === 'ended' || $new_status === 'declined') {
            $stmt = $pdo->prepare("UPDATE calls SET status = ? WHERE order_id = ? AND status != 'ended'");
            $stmt->execute([$new_status, $order_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE calls SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $call_id]);
        }
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
