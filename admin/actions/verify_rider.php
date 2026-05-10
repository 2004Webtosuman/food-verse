<?php
// admin/actions/verify_rider.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_admin()) {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    $valid_actions = ['verified', 'rejected'];

    if ($user_id > 0 && in_array($action, $valid_actions)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET verification_status = ? WHERE id = ?");
            $stmt->execute([$action, $user_id]);

            $_SESSION['flash_message'] = "Rider verification status updated to " . ucfirst($action) . ".";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "Error updating status: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_message'] = "Invalid request parameters.";
    }

    redirect('../view_user.php?id=' . $user_id);
} else {
    redirect('../dashboard.php');
}
