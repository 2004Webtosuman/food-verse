<?php
// delivery/actions/update_mission_status.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/mail_helper.php';

if (!is_delivery()) {
    $_SESSION['flash_message'] = "Access denied.";
    redirect('../../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitize($_POST['new_status']);
    $user_id = $_SESSION['user_id'];

    $allowed = ['out_for_delivery', 'delivered'];
    if (in_array($new_status, $allowed)) {
        try {
            // Ensure this rider actually owns this mission
            $stmt = $pdo->prepare("SELECT user_id, status FROM orders WHERE id = ? AND delivery_user_id = ?");
            $stmt->execute([$order_id, $user_id]);
            $order = $stmt->fetch();

            if ($order) {
                // Update the status (and auto complete COD payments if delivered)
                if ($new_status === 'delivered') {
                    $pdo->prepare("UPDATE orders SET status = ?, payment_status = 'paid' WHERE id = ?")->execute([$new_status, $order_id]);
                } else {
                    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $order_id]);
                }

                $customer_id = $order['user_id'];

                // Send In-App Notification to Customer
                if ($new_status === 'out_for_delivery') {
                    add_notification($customer_id, "Order On The Way! 🚀", "Your rider has picked up your order and is heading towards you.", "customer/track_order.php?id=$order_id");
                } elseif ($new_status === 'delivered') {
                    add_notification($customer_id, "Order Delivered! 🎉", "Your food has safely arrived. Enjoy!", "customer/orders.php");
                }

                // Send Email Notification to Customer
                triggerOrderEmail($order_id, 'status_update');

                $_SESSION['flash_message'] = "✅ Mission status updated to: " . strtoupper(str_replace('_', ' ', $new_status));
            } else {
                $_SESSION['flash_message'] = "❌ Mission not found or unauthorized.";
            }
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "❌ Database Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_message'] = "❌ Invalid mission state.";
    }

    redirect("../view_order.php?id=$order_id");
}
