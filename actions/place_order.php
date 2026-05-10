<?php
// place_order.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in() || empty($_SESSION['cart'])) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $total_price = (float)$_POST['total_price'];
    $payment_method = isset($_POST['payment_method']) ? sanitize($_POST['payment_method']) : 'cod';
    
    try {
        $pdo->beginTransaction();
        
        // 1. Create Order
        // Get user's saved location coordinates
        $lat = $_SESSION['user_location']['latitude'] ?? null;
        $lng = $_SESSION['user_location']['longitude'] ?? null;

        // If not in session, try fetching from user profile
        if ($lat === null || $lng === null) {
            $u_stmt = $pdo->prepare("SELECT latitude, longitude FROM users WHERE id = ?");
            $u_stmt->execute([$user_id]);
            $u_loc = $u_stmt->fetch();
            $lat = $u_loc['latitude'] ?? 27.7172; // Fallback to Kathmandu centroid
            $lng = $u_loc['longitude'] ?? 85.3240;
        }

        // Restaurant coordinates (SET TO NULL TO TRIGGER DYNAMIC OSM GEOCODING)
        $res_lat = null;
        $res_lng = null;

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status, payment_method, restaurant_lat, restaurant_lng, delivery_lat, delivery_lng) VALUES (?, ?, 'pending', ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $total_price, $payment_method, $res_lat, $res_lng, $lat, $lng]);
        $order_id = $pdo->lastInsertId();
        
        // 2. Validate stock & create Order Items
        $out_of_stock_items = [];
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $stmt = $pdo->prepare("SELECT name, price, stock_quantity FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) continue;

            if ($product['stock_quantity'] < $quantity) {
                $out_of_stock_items[] = $quantity > $product['stock_quantity'] && $product['stock_quantity'] > 0
                    ? "\"{$product['name']}\" (only {$product['stock_quantity']} left)"
                    : "\"{$product['name']}\" (out of stock)";
            }
        }

        // If any item is out of stock, rollback and redirect
        if (!empty($out_of_stock_items)) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = "⚠️ Some items are unavailable: " . implode(', ', $out_of_stock_items) . ". Please update your cart.";
            $_SESSION['flash_type'] = "error";
            redirect('../customer/cart.php');
        }

        // All items in stock — insert order items and deduct inventory
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $price = $stmt->fetchColumn();

            // Insert order item
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $product_id, $price, $quantity]);

            // Deduct stock
            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$quantity, $product_id]);
        }
        
        // 3. Clear Cart
        $_SESSION['cart'] = [];
        
        $pdo->commit();
        
        // 4. Send Emails
        require_once '../includes/mail_helper.php';
        triggerOrderEmail($order_id, 'new_order');
        
        $_SESSION['flash_message'] = "Order placed successfully! Order ID: #$order_id";
        redirect('../customer/order_success.php?id=' . $order_id);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Order failed: " . $e->getMessage());
    }
}
?>
