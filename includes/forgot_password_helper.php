<?php
// includes/forgot_password_helper.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail_helper.php';

/**
 * Check if user exists and return their data
 */
function checkUserByEmail($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Generate 6-digit OTP
 */
function generateOTP() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Store OTP in database
 */
function storeOTP($pdo, $userId, $otp, $type) {
    // Delete existing OTPs for this user and type to avoid confusion
    $stmt = $pdo->prepare("DELETE FROM otp_verifications WHERE user_id = ? AND type = ?");
    $stmt->execute([$userId, $type]);

    // Use SQL NOW() + 10 mins to avoid PHP timezone mismatches
    $stmt = $pdo->prepare("INSERT INTO otp_verifications (user_id, otp, type, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
    return $stmt->execute([$userId, $otp, $type]);
}

/**
 * Send Email OTP using the existing mail system
 */
function sendEmailOTP($email, $name, $otp) {
    $primaryColor = '#FF6B35';
    $appBg = '#FFF8F2';
    
    $subject = "Verification Code: $otp - FoodVerse";
    $body = "
    <div style='font-family: Arial, sans-serif; background-color: $appBg; padding: 40px 20px; text-align: center;'>
        <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 30px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);'>
            <h1 style='color: #1A1A1A; margin-bottom: 10px;'><span style='color: $primaryColor;'>Food</span>Verse</h1>
            <h2 style='color: #1A1A1A; font-size: 24px; margin-bottom: 20px;'>Security Verification</h2>
            <p style='color: #666; font-size: 16px; line-height: 1.5;'>Hi <strong>$name</strong>,<br>You requested to reset your password. Use the verification code below to continue:</p>
            
            <div style='background-color: #FDF2EC; border-radius: 20px; padding: 25px; margin: 30px 0;'>
                <span style='font-size: 36px; font-weight: 900; color: $primaryColor; letter-spacing: 8px;'>$otp</span>
            </div>
            
            <p style='color: #999; font-size: 13px;'>This code will expire in <strong>10 minutes</strong>.<br>If you didn't request this, please ignore this email.</p>
        </div>
        <p style='color: #AAA; font-size: 11px; margin-top: 20px;'>&copy; " . date('Y') . " FoodVerse. All rights reserved.</p>
    </div>";
    
    return sendMail($email, $subject, $body);
}

/**
 * Send SMS OTP (Placeholder for Twilio/Firebase)
 */
function sendSMSOTP($phone, $otp) {
    // THIS IS A PLACEHOLDER FOR ACTUAL SMS API INTEGRATION
    // Example: $twilio->messages->create($phone, ['from' => 'FoodVerse', 'body' => "Your OTP is $otp"]);
    
    // For now, we simulate success
    error_log("SMS OTP sent to $phone: $otp");
    return true;
}

/**
 * Verify OTP
 */
function verifyOTP($pdo, $userId, $otp) {
    $stmt = $pdo->prepare("SELECT * FROM otp_verifications WHERE user_id = ? AND otp = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId, $otp]);
    $verification = $stmt->fetch();

    if ($verification) {
        // Increment attempts if needed, or just let it pass
        // Delete the OTP after successful verification to prevent reuse
        $del = $pdo->prepare("DELETE FROM otp_verifications WHERE id = ?");
        $del->execute([$verification['id']]);
        return true;
    }
    return false;
}

/**
 * Final Password Reset
 */
function resetPassword($pdo, $userId, $newPassword) {
    $password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    return $stmt->execute([$password_hash, $userId]);
}
?>
