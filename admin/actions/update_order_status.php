<?php
// admin/actions/update_order_status.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_admin()) {
    $_SESSION['flash_message'] = "Access denied.";
    redirect('../../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id']);
    $status = sanitize($_POST['status']);

    $allowed_statuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
    if (in_array($status, $allowed_statuses)) {
        try {
            if ($status === 'delivered') {
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = 'paid' WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            }
            $stmt->execute([$status, $order_id]);
            
            // Send Emails
            require_once '../../includes/mail_helper.php';
            triggerOrderEmail($order_id, 'status_update');
            
            // Send In-App Notification to Customer
            $stmt_notif = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
            $stmt_notif->execute([$order_id]);
            $owner = $stmt_notif->fetch();
            if ($owner) {
                $status_msg = ucfirst(str_replace('_', ' ', $status));
                add_notification($owner['user_id'], "Order Update", "Your order is now: $status_msg", "customer/orders.php");
            }
            
            // Broadcast to nearest rider
            if ($status === 'confirmed') {
                assign_nearest_rider($order_id, true);
            }
            
            $_SESSION['flash_message'] = "Order #$order_id status updated to " . ucfirst($status) . ".";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "Error updating order: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_message'] = "Invalid status.";
    }

    redirect('../orders.php');
}
