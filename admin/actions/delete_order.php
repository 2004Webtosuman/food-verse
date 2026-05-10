<?php
// admin/actions/delete_order.php
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!is_admin()) redirect('../../login.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        // Fetch order to verify it is "unpaid"
        $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if ($order) {
            $paymentStatus = in_array($order['status'], ['paid', 'delivered']) ? 'PAID' : 'UNPAID';
            
            if ($paymentStatus === 'UNPAID') {
                // Delete associated order items first to satisfy foreign keys constraints if any
                $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$id]);
                
                // Delete the order
                $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);
                $_SESSION['flash_message'] = "Unpaid order #$id deleted successfully.";
            } else {
                $_SESSION['flash_message'] = "Cannot delete a paid or delivered order.";
            }
        } else {
            $_SESSION['flash_message'] = "Order not found.";
        }
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Cannot delete order. Error: " . $e->getMessage();
    }
}

redirect('../orders.php');
