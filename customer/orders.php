<?php
// orders.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

// Redirect riders to their dashboard
if (is_delivery()) {
    redirect('../delivery/dashboard.php');
}

$user_id = $_SESSION['user_id'];
$status_filter = $_GET['status'] ?? 'all';

$query = "SELECT o.*, o.delivery_user_id, d.full_name as rider_name, d.phone as rider_phone, d.profile_pic as rider_image 
         FROM orders o 
         LEFT JOIN users d ON o.delivery_user_id = d.id 
         WHERE o.user_id = ? ";
if ($status_filter === 'active') {
    $query .= "AND o.status IN ('pending', 'confirmed', 'preparing', 'out_for_delivery', 'paid') ";
} elseif ($status_filter === 'past') {
    $query .= "AND o.status IN ('delivered', 'cancelled') ";
}
$query .= "ORDER BY o.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Order History - FoodVerse</title>
</head>
<body class="bg-app-bg min-h-screen pb-24">

    <!-- Header -->
    <header class="p-6 bg-white flex items-center justify-between sticky top-0 z-40 shadow-sm border-b border-card-border">
        <div class="flex items-center gap-4">
            <button onclick="history.back()" class="p-2 bg-gray-50 rounded-full hover:bg-gray-100 transition-all border border-card-border">
                <i data-lucide="chevron-left" class="w-6 h-6 text-gray-700"></i>
            </button>
            <h1 class="text-2xl font-bold text-gray-900">Order History</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="notifications.php" class="relative group">
                <button class="bg-white p-2 rounded-full hover:bg-gray-50 transition-all shadow-sm border border-card-border">
                    <i data-lucide="bell" class="w-6 h-6 text-gray-700 group-hover:text-primary transition-colors"></i>
                    <span id="notif-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold opacity-0 transition-opacity">0</span>
                </button>
            </a>
            <a href="cart.php" class="relative">
                <button class="bg-white p-2 rounded-full hover:bg-gray-50 transition-all shadow-sm border border-card-border">
                    <i data-lucide="shopping-cart" class="w-6 h-6 text-primary"></i>
                    <span class="absolute -top-1 -right-1 bg-primary text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">
                        <?php echo get_cart_count(); ?>
                    </span>
                </button>
            </a>
            <a href="settings.php" class="p-2 bg-white rounded-full hover:bg-gray-50 transition-all shadow-sm border border-card-border">
                <i data-lucide="settings" class="w-6 h-6 text-gray-500"></i>
            </a>
        </div>
    </header>

    <!-- Tabs -->
    <div class="px-6 mb-6 flex gap-3 mt-4">
        <a href="orders.php?status=active" class="flex-1 text-center py-3 px-4 <?php echo $status_filter === 'active' ? 'bg-primary text-white border-primary' : 'bg-white text-gray-500 border-card-border'; ?> rounded-2xl font-bold text-sm border hover:border-primary/30 transition-all">Active</a>
        <a href="orders.php?status=past" class="flex-1 text-center py-3 px-4 <?php echo $status_filter === 'past' ? 'bg-primary text-white border-primary' : 'bg-white text-gray-500 border-card-border'; ?> rounded-2xl font-bold text-sm border hover:border-primary/30 transition-all">Past Orders</a>
    </div>

    <div class="p-6 space-y-4">
        <?php foreach ($orders as $order): ?>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-card-border group hover:shadow-md hover:border-primary/20 transition-all">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider mb-1">ORDER ID</p>
                        <h3 class="font-bold text-gray-800 group-hover:text-primary transition-colors">#FE-<?php echo $order['id']; ?></h3>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-gray-400 mb-2"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $order['status'] === 'delivered' ? 'bg-green-50 text-green-600' : 'bg-yellow-50 text-yellow-600'; ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Rider Info (if assigned) -->
                <?php if (!empty($order['delivery_user_id']) && !empty($order['rider_name'])): ?>
                    <a href="rider_profile.php?id=<?php echo $order['delivery_user_id']; ?>&order_id=<?php echo $order['id']; ?>" 
                       class="flex items-center gap-3 p-3 mb-4 bg-blue-50 rounded-2xl border border-blue-100 hover:border-blue-300 transition-all">
                        <div class="w-10 h-10 rounded-xl overflow-hidden border border-blue-200 flex-shrink-0">
                            <?php 
                                $rImg = (!empty($order['rider_image']) && file_exists('../' . $order['rider_image']))
                                    ? '../' . $order['rider_image']
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($order['rider_name']) . '&background=FF6B35&color=fff&size=40';
                            ?>
                            <img src="<?php echo $rImg; ?>" alt="Rider" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-gray-800 truncate"><?php echo htmlspecialchars($order['rider_name']); ?></p>
                            <p class="text-[10px] text-blue-500 font-bold uppercase tracking-wider">
                                <?php 
                                    if ($order['status'] === 'out_for_delivery') echo '🏍️ On the way';
                                    elseif ($order['status'] === 'delivered') echo '✅ Delivered your order';
                                    else echo '📋 Assigned to your order';
                                ?>
                            </p>
                        </div>
                        <?php if (!empty($order['rider_phone'])): ?>
                            <span class="text-[10px] text-gray-400 font-mono"><?php echo $order['rider_phone']; ?></span>
                        <?php endif; ?>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-blue-300 flex-shrink-0"></i>
                    </a>
                <?php elseif ($order['status'] === 'confirmed' && empty($order['delivery_user_id'])): ?>
                    <div class="flex items-center gap-3 p-3 mb-4 bg-yellow-50 rounded-2xl border border-yellow-100 italic">
                        <div class="w-10 h-10 rounded-xl bg-yellow-100 border border-yellow-200 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="search" class="w-5 h-5 text-yellow-600 animate-pulse"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-bold text-yellow-700">Searching for rider...</p>
                            <p class="text-[10px] text-yellow-600 mt-0.5">Finding the nearest rider for your order</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    <!-- Total Amount -->
                    <div class="flex items-center gap-4 flex-1 p-4 bg-section-bg rounded-2xl border border-card-border">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center border border-card-border shadow-sm">
                            <i data-lucide="package" class="w-5 h-5 text-primary"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-400 text-[10px] uppercase tracking-wider">Total Amount</h4>
                            <p class="font-black text-base text-primary">Rs. <?php echo number_format($order['total_price'], 0); ?></p>
                        </div>
                    </div>

                    <!-- Action Buttons Container -->
                    <div class="flex gap-2 w-full sm:w-auto">
                        <?php if ($order['status'] !== 'cancelled'): ?>
                            <a href="track_order.php?id=<?php echo $order['id']; ?>" class="flex-1 sm:flex-none h-14 sm:h-16 px-6 bg-primary text-white rounded-2xl flex items-center justify-center gap-2 hover:bg-primary-hover transition-all shadow-lg shadow-primary/20">
                                <i data-lucide="map-pin" class="w-4 h-4 text-white"></i>
                                <span class="text-xs font-bold uppercase tracking-widest">Track</span>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'delivered' && !empty($order['delivery_user_id'])): ?>
                            <a href="rider_profile.php?id=<?php echo $order['delivery_user_id']; ?>&order_id=<?php echo $order['id']; ?>" class="flex-1 sm:flex-none h-14 sm:h-16 px-6 bg-yellow-500 text-white rounded-2xl flex items-center justify-center gap-2 hover:bg-yellow-600 transition-all shadow-lg shadow-yellow-500/20">
                                <i data-lucide="star" class="w-4 h-4 text-white"></i>
                                <span class="text-xs font-bold uppercase tracking-widest">Rate</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
