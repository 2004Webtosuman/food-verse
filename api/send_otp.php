<?php
// api/send_otp.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/forgot_password_helper.php';

$is_reset = isset($_SESSION['reset_user_id']);
$is_2fa = isset($_SESSION['pending_2fa_user_id']);

if (!$is_reset && !$is_2fa) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized or no pending session.']);
    exit;
}

$user_id = $is_reset ? $_SESSION['reset_user_id'] : $_SESSION['pending_2fa_user_id'];
$email = $is_reset ? $_SESSION['reset_email'] : $_SESSION['pending_2fa_email'];
$name = $is_reset ? ($_SESSION['reset_name'] ?? 'User') : 'Valued Customer';

// Basic rate limiting
if (!isset($_SESSION['otp_attempts_remaining'])) {
    $_SESSION['otp_attempts_remaining'] = 5;
}

if ($_SESSION['otp_attempts_remaining'] <= 0) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Maximum OTP attempts reached. Please try later.']);
    exit;
}

// Generate OTP
$otp = generateOTP();

try {
    if ($is_reset) {
        // Use forgot_password_helper flow (otp_verifications table)
        if (storeOTP($pdo, $user_id, $otp, 'email')) {
            if (sendEmailOTP($email, $name, $otp)) {
                echo json_encode(['success' => true, 'message' => 'New code sent to your email.']);
            } else {
                throw new Exception("Failed to send email.");
            }
        } else {
            throw new Exception("Failed to store OTP.");
        }
    } else {
        // Use 2FA flow (users table)
        $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
        $stmt->execute([$otp_hash, $expiry, $user_id]);
        
        require_once '../includes/mail_helper.php';
        $subject = "Your FoodVerse Two-Factor Authentication Code";
        $body = "<h2>FoodVerse Security</h2><p>Your OTP code is: <strong>{$otp}</strong></p><p>This code will expire in 5 minutes. Do not share it with anyone.</p>";
        
        if (sendMail($email, $subject, $body)) {
             echo json_encode(['success' => true, 'message' => 'OTP sent successfully.']);
        } else {
            throw new Exception("Mail failed to send.");
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
