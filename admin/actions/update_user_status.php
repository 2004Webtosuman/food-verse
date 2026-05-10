<?php
// admin/actions/update_user_status.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_admin()) {
    $_SESSION['flash_message'] = "Access denied.";
    redirect('../../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $status = sanitize($_POST['status']);

    if (in_array($status, ['active', 'suspended'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $user_id]);
            $_SESSION['flash_message'] = "User account " . ($status === 'suspended' ? 'suspended' : 'activated') . " successfully.";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "Error updating user: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_message'] = "Invalid status.";
    }

    redirect('../view_user.php?id=' . $user_id);
}
