<?php
// api/update_settings.php
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
    
    // Categorize updates based on incoming structured data
    try {
        $pdo->beginTransaction();

        // 1. Theme Configuration
        if (isset($data['theme'])) {
            $theme = in_array($data['theme'], ['light', 'dark', 'system']) ? $data['theme'] : 'system';
            $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
            $stmt->execute([$theme, $user_id]);
        }
        
        // 2. Notifications Configuration
        if (isset($data['notification'])) {
            $notifJSON = json_encode($data['notification']);
            $stmt = $pdo->prepare("UPDATE users SET notification_settings = ? WHERE id = ?");
            $stmt->execute([$notifJSON, $user_id]);
        }
        
        // 3. Privacy Configuration
        if (isset($data['privacy'])) {
            $privJSON = json_encode($data['privacy']);
            $stmt = $pdo->prepare("UPDATE users SET privacy_settings = ? WHERE id = ?");
            $stmt->execute([$privJSON, $user_id]);
        }

        // 4. Delivery/Rider Configuration
        if (isset($data['rider']) && is_delivery()) {
            $riderJSON = json_encode($data['rider']);
            $stmt = $pdo->prepare("UPDATE users SET rider_settings = ? WHERE id = ?");
            $stmt->execute([$riderJSON, $user_id]);
        }
        
        // 5. Accessibility Configuration
        if (isset($data['accessibility'])) {
            $accessJSON = json_encode($data['accessibility']);
            $stmt = $pdo->prepare("UPDATE users SET accessibility_settings = ? WHERE id = ?");
            $stmt->execute([$accessJSON, $user_id]);
        }

        // 6. Security (Password Update)
        if (isset($data['security']['current_password']) && isset($data['security']['new_password'])) {
            // Verify current password first
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_hash = $stmt->fetchColumn();
            
            if (password_verify($data['security']['current_password'], $current_hash)) {
                $new_hash = password_hash($data['security']['new_password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$new_hash, $user_id]);
            } else {
                throw new Exception("Incorrect current password.");
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Settings saved successfully.']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
