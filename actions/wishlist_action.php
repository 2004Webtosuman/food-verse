<?php
// wishlist_action.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($product_id <= 0) {
        redirect('../index.php');
    }

    try {
        if ($action === 'toggle' || $action === 'add_to_wishlist') {
            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            if ($stmt->fetch()) {
                if ($action === 'toggle') {
                    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$user_id, $product_id]);
                    $_SESSION['flash_message'] = "Removed from wishlist";
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $product_id]);
                $_SESSION['flash_message'] = "Added to wishlist";
            }
        } elseif ($action === 'remove') {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $_SESSION['flash_message'] = "Removed from wishlist";
        }
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Database error";
    }

    $redirect = $_SERVER['HTTP_REFERER'] ?? '../index.php';
    redirect($redirect);
}
?>
