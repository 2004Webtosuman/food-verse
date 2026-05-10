<?php
// actions/process_esewa.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in() || empty($_SESSION['cart'])) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $total_price = (float)$_POST['total_price'];
    $payment_method = 'esewa';
    
    try {
        $pdo->beginTransaction();
        
        // 1. Get Location & Create Pending Order
        $lat = $_SESSION['user_location']['latitude'] ?? null;
        $lng = $_SESSION['user_location']['longitude'] ?? null;
        if ($lat === null || $lng === null) {
            $u_stmt = $pdo->prepare("SELECT latitude, longitude FROM users WHERE id = ?");
            $u_stmt->execute([$user_id]);
            $u_loc = $u_stmt->fetch();
            $lat = $u_loc['latitude'] ?? 27.7172; 
            $lng = $u_loc['longitude'] ?? 85.3240;
        }

        $res_lat = null;
        $res_lng = null;

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status, payment_method, restaurant_lat, restaurant_lng, delivery_lat, delivery_lng) VALUES (?, ?, 'pending', ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $total_price, $payment_method, $res_lat, $res_lng, $lat, $lng]);
        $order_id = $pdo->lastInsertId();
        
        // 2. Create Order Items
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $price = $stmt->fetchColumn() ?: (($product_id == 1) ? 1000 : 620);
            
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $product_id, $price, $quantity]);
        }
        
        // 3. Generate eSewa v2 signature
        $transaction_uuid = $order_id . '-' . time();
        
        // Update order with transaction_uuid
        $stmt = $pdo->prepare("UPDATE orders SET transaction_uuid = ? WHERE id = ?");
        $stmt->execute([$transaction_uuid, $order_id]);
        
        $pdo->commit();
        
        // eSewa v2 Config
        $secret_key = "8gBm/:&EnhH.1/q";
        $product_code = "EPAYTEST";
        $url = "https://rc-epay.esewa.com.np/api/epay/main/v2/form";
        
        // Signature string: total_amount=X,transaction_uuid=Y,product_code=Z
        // Note: eSewa v2 requires fields in this exact order for signature
        $signature_string = "total_amount=$total_price,transaction_uuid=$transaction_uuid,product_code=$product_code";
        $hash = hash_hmac('sha256', $signature_string, $secret_key, true);
        $signature = base64_encode($hash);
        
        // Form Data
        $data = [
            'amount' => $total_price,
            'tax_amount' => 0,
            'total_amount' => $total_price,
            'transaction_uuid' => $transaction_uuid,
            'product_code' => $product_code,
            'product_service_charge' => 0,
            'product_delivery_charge' => 0,
            'success_url' => "http://localhost/food-verse/actions/esewa_success.php",
            'failure_url' => "http://localhost/food-verse/actions/esewa_failure.php",
            'signed_field_names' => "total_amount,transaction_uuid,product_code",
            'signature' => $signature
        ];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Processing Payment...</title>
</head>
<body onload="document.getElementById('esewaForm').submit();">
    <div style="text-align: center; margin-top: 50px; font-family: sans-serif;">
        <h2>Redirecting to eSewa...</h2>
        <p>Please do not refresh the page.</p>
        <form id="esewaForm" action="<?php echo $url; ?>" method="POST">
            <?php foreach ($data as $key => $value): ?>
                <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>">
            <?php endforeach; ?>
        </form>
    </div>
</body>
</html>
<?php
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Payment processing failed: " . $e->getMessage());
    }
}
?>
