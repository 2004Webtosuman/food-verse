<?php
// order_success.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Order Success - FoodVerse</title>
</head>
<body class="bg-app-bg min-h-screen flex flex-col items-center justify-center px-8 text-center">

    <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mb-8 animate-bounce border border-green-200">
        <i data-lucide="check" class="w-12 h-12 text-green-500"></i>
    </div>

    <h1 class="text-3xl font-bold text-gray-900 mb-4">Order Successful!</h1>
    <p class="text-gray-400 mb-12 max-w-xs mx-auto">
        Your order #<?php echo $order_id; ?> has been placed and is being prepared with love.
    </p>

    <div class="w-full max-w-sm space-y-4">
        <a href="orders.php" class="block w-full py-5 bg-primary text-white rounded-[24px] font-bold shadow-md hover:bg-primary-hover transition-all">
            Track My Order
        </a>
        <a href="../index.php" class="block w-full py-5 bg-white border border-card-border text-gray-900 rounded-[24px] font-bold shadow-sm hover:bg-gray-50 transition-all">
            Back to Home
        </a>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
