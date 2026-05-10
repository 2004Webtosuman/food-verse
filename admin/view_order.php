<?php
// admin/view_order.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_admin()) redirect('../login.php');

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$order = null;
$items = [];

try {
    $stmt = $pdo->prepare("SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order) {
        $items_stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $items_stmt->execute([$order_id]);
        $items = $items_stmt->fetchAll();
    }
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Error: " . $e->getMessage();
}

if (!$order) {
    $_SESSION['flash_message'] = "Order not found.";
    redirect('orders.php');
}
?>
<head>
    <?php include 'includes/head.php'; ?>
    <title>Order Detail #<?php echo $order['id']; ?> - FoodVerse</title>
</head>
<body class="bg-app-bg dark:bg-dark-bg min-h-screen text-gray-900 dark:text-gray-100 transition-colors">

    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-dark-card border-r border-card-border dark:border-dark-border hidden md:flex flex-col h-full z-10 flex-shrink-0 transition-colors">
            <div class="p-6 border-b border-card-border dark:border-dark-border flex items-center h-[88px] gap-2">
                <h1 class="text-xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                <span class="bg-primary/10 text-primary text-[8px] font-bold px-2 py-0.5 rounded-full uppercase ml-1">Admin Panel</span>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 py-6 space-y-2 px-4 overflow-y-auto w-full">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="products.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="package" class="w-5 h-5"></i>
                    <span>Products</span>
                </a>
                <a href="orders.php" class="flex items-center gap-3 px-4 py-3 bg-primary/5 dark:bg-primary/10 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
                    <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                    <span>Orders</span>
                </a>
                <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    <span>Users</span>
                </a>
                <a href="categories.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="tags" class="w-5 h-5"></i>
                    <span>Categories</span>
                </a>
                <a href="reports.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="bar-chart-2" class="w-5 h-5"></i>
                    <span>Reports</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-full overflow-hidden relative">
            
            <!-- Topbar -->
            <?php include 'includes/topbar.php'; ?>

            <main class="flex-1 overflow-y-auto p-4 md:p-8 w-full bg-app-bg dark:bg-dark-bg pb-28 md:pb-8 transition-colors">
                <!-- Compact Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <a href="orders.php" class="inline-flex items-center gap-1.5 text-gray-400 hover:text-primary transition-all font-bold text-[10px] uppercase tracking-widest mb-1">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Orders
                        </a>
                        <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">Order #<?php echo $order['id']; ?></h2>
                    </div>
                    <?php 
                        $status_class = match($order['status']) {
                            'delivered'       => 'bg-green-50 text-green-600 border-green-200',
                            'out_for_delivery'=> 'bg-blue-50 text-blue-500 border-blue-200',
                            'preparing'       => 'bg-yellow-50 text-yellow-600 border-yellow-200',
                            'confirmed', 'pending', 'paid' => 'bg-orange-50 text-orange-500 border-orange-200',
                            'cancelled'       => 'bg-red-50 text-red-600 border-red-200',
                            default           => 'bg-gray-50 text-gray-600 border-gray-200',
                        };
                    ?>
                    <div class="self-start sm:self-center px-4 py-2 rounded-xl font-bold uppercase text-[10px] tracking-widest border <?php echo $status_class; ?>">
                        <?php echo str_replace('_', ' ', $order['status']); ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-8">
                        <div class="bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-6 md:p-8 transition-colors">
                            <h3 class="text-xl font-bold mb-6 flex items-center gap-2 text-gray-900 dark:text-white transition-colors">
                                <i data-lucide="package" class="w-5 h-5 text-gray-400"></i>
                                Order Items
                            </h3>
                            <div class="divide-y divide-card-border dark:divide-dark-border">
                                <?php foreach ($items as $item): ?>
                                <div class="py-5 flex items-center gap-4">
                                    <div class="w-16 h-16 bg-white dark:bg-dark-bg rounded-xl overflow-hidden border border-card-border dark:border-dark-border shadow-sm flex-shrink-0 transition-colors">
                                        <img src="../<?php echo !empty($item['image_url']) ? $item['image_url'] : 'images/placeholder.png'; ?>" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-900 dark:text-white text-sm transition-colors"><?php echo sanitize($item['product_name']); ?></h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 transition-colors">Qty: <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-black text-gray-900 dark:text-white transition-colors">Rs. <?php echo number_format($item['price'] * $item['quantity'], 0); ?></p>
                                        <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mt-1">Rs. <?php echo number_format($item['price'], 0); ?> each</p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-8 pt-6 border-t border-card-border dark:border-dark-border flex justify-between items-center transition-colors">
                                <span class="text-gray-400 dark:text-gray-500 font-bold uppercase text-xs tracking-wider transition-colors">Total Amount</span>
                                <span class="text-2xl font-black text-gray-900 dark:text-white transition-colors">Rs. <?php echo number_format($order['total_price'], 0); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <div class="bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-6 md:p-8 transition-colors">
                            <h3 class="text-xl font-bold mb-6 flex items-center gap-2 text-gray-900 dark:text-white transition-colors">
                                <i data-lucide="user" class="w-5 h-5 text-gray-400"></i>
                                Customer Info
                            </h3>
                            <div class="space-y-5">
                                <div>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-widest mb-1 transition-colors">Name</p>
                                    <p class="font-bold text-gray-900 dark:text-white text-sm transition-colors"><?php echo sanitize($order['full_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-widest mb-1 transition-colors">Email</p>
                                    <p class="font-medium text-gray-600 dark:text-gray-300 text-sm transition-colors"><?php echo sanitize($order['email']); ?></p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-widest mb-1 transition-colors">Address</p>
                                    <p class="font-medium text-gray-600 dark:text-gray-300 text-sm leading-relaxed transition-colors"><?php echo !empty($order['delivery_address']) ? sanitize($order['delivery_address']) : 'Not specify'; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-6 md:p-8 transition-colors">
                            <h3 class="text-xl font-bold mb-6 flex items-center gap-2 text-gray-900 dark:text-white transition-colors">
                                <i data-lucide="info" class="w-5 h-5 text-gray-400"></i>
                                Modify Status
                            </h3>
                            <form action="actions/update_order_status.php" method="POST" class="space-y-4">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" class="w-full bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white dark:focus:bg-dark-bg transition-all text-gray-900 dark:text-white font-medium transition-colors">
                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                    <option value="out_for_delivery" <?php echo $order['status'] == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button type="submit" class="w-full bg-primary text-white py-4 rounded-xl font-bold shadow-sm hover:bg-primary-hover active:scale-[0.98] transition-all uppercase tracking-wider text-sm">
                                    Update Status
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script> lucide.createIcons(); </script>
</body>
</html>
