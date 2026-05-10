<?php
// delivery/actions/accept_order.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_delivery()) {
    $_SESSION['flash_message'] = "Access denied.";
    redirect('../../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // We only accept the order if it's still 'confirmed' and no rider is assigned.
        // This prevents multiple riders from accepting the same order.
        $stmt = $pdo->prepare("UPDATE orders 
                               SET delivery_user_id = ?, status = 'preparing' 
                               WHERE id = ? AND status = 'confirmed' AND delivery_user_id IS NULL");
        $stmt->execute([$user_id, $order_id]);

        if ($stmt->rowCount() > 0) {
            // Find who the customer is to notify them
            $cust_stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
            $cust_stmt->execute([$order_id]);
            $owner = $cust_stmt->fetch();
            
            if ($owner) {
                // In-app alert
                add_notification($owner['user_id'], "Rider Assigned! 🛵", "Your order has been picked up by a rider and is now being prepared.", "customer/track_order.php?id=$order_id");
                
                // Email alert
                require_once '../../includes/mail_helper.php';
                triggerOrderEmail($order_id, 'status_update');
            }

            $_SESSION['flash_message'] = "✅ Order #FE-$order_id accepted successfully!";
        } else {
            $_SESSION['flash_message'] = "❌ Sorry, this order has already been picked by another rider or updated.";
        }
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "❌ Error: " . $e->getMessage();
    }

    redirect('../dashboard.php');
}
