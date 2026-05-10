<?php
// delivery/actions/update_profile_docs.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_delivery()) {
    die("Access denied.");
}

$user_id = $_SESSION['user_id'];
$upload_dir = '../../uploads/riders/docs/';

$allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
$max_size = 5 * 1024 * 1024; // 5MB

$files_to_upload = [
    'profile_pic'   => 'profile_pic',
    'license_doc'   => 'license_doc',
    'insurance_doc' => 'insurance_doc',
    'id_card_doc'   => 'id_card_doc'
];

$updates = [];
$params = [];

try {
    foreach ($files_to_upload as $input_name => $db_column) {
        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$input_name];
            
            // Validation
            if (!in_array($file['type'], $allowed_types)) {
                $_SESSION['flash_message'] = "Invalid file type for $input_name. Only JPG, PNG, and PDF are allowed.";
                redirect('../profile.php');
            }
            if ($file['size'] > $max_size) {
                $_SESSION['flash_message'] = "File too large for $input_name. Max size is 5MB.";
                redirect('../profile.php');
            }

            // Generate filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $input_name . "_" . $user_id . "_" . time() . "." . $ext;
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Save relative path for database
                $db_path = 'uploads/riders/docs/' . $filename;
                $updates[] = "$db_column = ?";
                $params[] = $db_path;

                // Special case: Profile picture might be expected in 'uploads/profiles/' by other parts of the system
                if ($input_name === 'profile_pic') {
                    // Update session for immediate display
                    $_SESSION['user_image'] = $db_path;
                }
            }
        }
    }

    if (!empty($updates)) {
        // Set verification status to pending if any document was uploaded
        // (Assuming if they upload something, they want it verified)
        $updates[] = "verification_status = 'pending'";
        
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['flash_message'] = "Documents uploaded successfully. Awaiting administrative verification.";
    } else {
        $_SESSION['flash_message'] = "No files were selected for upload.";
    }

} catch (Exception $e) {
    $_SESSION['flash_message'] = "Critical System Error: " . $e->getMessage();
}

redirect('../profile.php');
