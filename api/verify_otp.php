<?php
// api/verify_otp.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/forgot_password_helper.php';

$is_reset = isset($_SESSION['reset_user_id']);
$is_2fa = isset($_SESSION['pending_2fa_user_id']);

if (!$is_reset && !$is_2fa) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized or session expired.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $submitted_otp = $data['otp'] ?? '';
    
    if (strlen($submitted_otp) !== 6) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP format.']);
        exit;
    }

    $user_id = $is_reset ? $_SESSION['reset_user_id'] : $_SESSION['pending_2fa_user_id'];

    if (!isset($_SESSION['otp_attempts_remaining'])) {
        $_SESSION['otp_attempts_remaining'] = 5;
    }

    if ($_SESSION['otp_attempts_remaining'] <= 0) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Account locked due to too many failed attempts.']);
        exit;
    }

    if ($is_reset) {
        // Reset Flow Verification
        if (verifyOTP($pdo, $user_id, $submitted_otp)) {
            $_SESSION['otp_verified'] = true;
            echo json_encode([
                'success' => true, 
                'flow' => 'reset',
                'message' => 'Verification successful!'
            ]);
            exit;
        }
    } else {
        // 2FA Flow Verification
        $stmt = $pdo->prepare("SELECT otp_code, otp_expiry, full_name, profile_pic FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && !empty($user['otp_code']) && strtotime($user['otp_expiry']) >= time()) {
            if (password_verify($submitted_otp, $user['otp_code'])) {
                // SUCCESS: Elevate to full session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $_SESSION['pending_2fa_role'];
                $_SESSION['profile_pic'] = $user['profile_pic'];
                
                // Clean up pending session
                unset($_SESSION['pending_2fa_user_id']);
                unset($_SESSION['pending_2fa_role']);
                unset($_SESSION['pending_2fa_email']);
                unset($_SESSION['otp_attempts_remaining']);
                
                // Clear OTP from DB
                $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL, otp_attempts = 0 WHERE id = ?")->execute([$user_id]);

                echo json_encode([
                    'success' => true, 
                    'flow' => '2fa',
                    'role' => $_SESSION['user_role'],
                    'message' => 'Verification successful!'
                ]);
                exit;
            }
        }
    }

    // FAIL Case (Both flows fall through here on failure)
    $_SESSION['otp_attempts_remaining']--;
    $pdo->prepare("UPDATE users SET otp_attempts = otp_attempts + 1 WHERE id = ?")->execute([$user_id]);

    echo json_encode([
        'success' => false, 
        'message' => "Incorrect or expired OTP. " . $_SESSION['otp_attempts_remaining'] . " attempts remaining."
    ]);
    exit;
}
?>
