<?php
// admin/actions/send_user_email.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/mail_helper.php';

if (!is_admin()) redirect('../../login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Increase limits for large media attachments
    ini_set('memory_limit', '256M');
    set_time_limit(300);

    $user_id = intval($_POST['user_id']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);

    try {
        $stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_message'] = "User not found.";
            redirect("../view_user.php?id=$user_id");
        }

        $toEmail = $user['email'];
        $toName = $user['full_name'];

        // Handle multiple attachments and validate total size (25MB limit)
        $attachmentPaths = [];
        $attachmentNames = [];
        $totalSize = 0;
        $maxSize = 25 * 1024 * 1024; // 25 MB

        if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
            // First pass: validate total size
            foreach ($_FILES['attachments']['size'] as $size) {
                $totalSize += $size;
            }
            
            if ($totalSize > $maxSize) {
                $_SESSION['flash_message'] = "Total attachment size exceeds the 25MB limit. Please upload smaller files or external links.";
                redirect("../view_user.php?id=$user_id");
            }
            
            $uploadTmpDir = sys_get_temp_dir();
            $fileCount = count($_FILES['attachments']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                    $originalName = basename($_FILES['attachments']['name'][$i]);
                    // Generate a secure temp file path
                    $tempPath = tempnam($uploadTmpDir, 'eml_') . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $originalName);
                    
                    if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $tempPath)) {
                        $attachmentPaths[] = $tempPath;
                        $attachmentNames[] = $originalName;
                    }
                }
            }
        }

        // Email UI Template
        $primaryColor = '#FF6B35';
        $appBg = '#FFF8F2';
        
        $bodyTemplate = "
        <div style='font-family: Arial, sans-serif; background-color: $appBg; padding: 60px 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 40px; padding: 50px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); text-align: center;'>
                <h1 style='color: #1A1A1A; margin: 0; font-size: 32px; font-weight: 900;'><span style='color: $primaryColor;'>Food</span>Express</h1>
                <div style='margin-top: 20px; font-size: 14px; font-weight: bold; color: #FF6B35; text-transform: uppercase; letter-spacing: 2px;'>Admin Notification</div>
                
                <div style='text-align: left; margin-top: 40px; border-top: 1px solid #F1EAE4; padding-top: 30px;'>
                    <p style='color: #1A1A1A; font-size: 18px; font-weight: bold;'>Hi {$toName},</p>
                    <div style='color: #555; font-size: 16px; line-height: 1.6; white-space: pre-wrap;'>{$message}</div>
                </div>

                <div style='margin-top: 50px; padding-top: 30px; border-top: 1px solid #F1EAE4; text-align: left;'>
                    <p style='color: #888; font-size: 12px; margin: 0;'>This is an official message from the FoodVerse administration team.</p>
                </div>
            </div>
        </div>";

        // Send Email with multiple attachments
        $success = sendMail($toEmail, $subject, $bodyTemplate, $attachmentPaths, $attachmentNames);

        // Delete temporary attachment files
        foreach ($attachmentPaths as $path) {
            if ($path && file_exists($path)) {
                unlink($path);
            }
        }

        if ($success) {
            $_SESSION['flash_message'] = "Email successfully sent to {$toEmail}.";
        } else {
            $_SESSION['flash_message'] = "Failed to send email. Check mail server configuration.";
        }

    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Database error: " . $e->getMessage();
    }

    redirect("../view_user.php?id=$user_id");
}
