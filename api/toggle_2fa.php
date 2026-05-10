<?php
// api/toggle_2fa.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = $_SESSION['user_id'];
    
    if (isset($data['is_2fa_enabled'])) {
        $enabled = $data['is_2fa_enabled'] ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_2fa_enabled = ? WHERE id = ?");
            $stmt->execute([$enabled, $user_id]);
            
            $state = $enabled ? "enabled" : "disabled";
            echo json_encode(['success' => true, 'message' => "Two-Factor Authentication securely $state."]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    }
}
?>
