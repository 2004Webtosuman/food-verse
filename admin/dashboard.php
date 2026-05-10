<?php
// admin/dashboard.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Simple Admin Check
if (!is_admin()) {
    $_SESSION['flash_message'] = "Access denied. Admin privileges required.";
    redirect('../login.php');
}

// Fetch stats
$stats = [
    'total_orders' => 0,
    'total_sales' => 0,
    'total_users' => 0,
    'low_stock' => 0
];

try {
    $stats['total_orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['total_sales'] = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    $stats['low_stock'] = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10")->fetchColumn();

    // Fetch Recent Orders
    $stmt = $pdo->query("SELECT o.*, u.full_name, u.email 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        ORDER BY o.created_at DESC 
                        LIMIT 10");
    $recent_orders = $stmt->fetchAll();

    // Fetch Recent System Feedback
    $fbStmt = $pdo->query("SELECT sf.*, u.full_name, u.profile_pic 
                           FROM system_feedback sf 
                           JOIN users u ON sf.user_id = u.id 
                           ORDER BY sf.created_at DESC 
                           LIMIT 10");
    $recent_feedback = $fbStmt->fetchAll();

    // Fetch Recent Product Reviews
    $revStmt = $pdo->query("SELECT pr.*, u.full_name, u.profile_pic, p.name as product_name 
                            FROM product_reviews pr 
                            JOIN users u ON pr.user_id = u.id 
                            JOIN products p ON pr.product_id = p.id 
                            ORDER BY pr.created_at DESC 
                            LIMIT 10");
    $recent_reviews = $revStmt->fetchAll();

} catch (PDOException $e) {
    // Fallback for demo
    $stats = ['total_orders' => 0, 'total_sales' => 0, 'total_users' => 0, 'low_stock' => 0];
    $recent_orders = [];
    $recent_feedback = [];
    $recent_reviews = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/head.php'; ?>
    <title>Admin Dashboard - Foodverse</title>
</head>

<body class="bg-app-bg dark:bg-dark-bg min-h-screen text-gray-900 dark:text-gray-100 transition-colors">

    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-dark-card border-r border-card-border dark:border-dark-border hidden md:flex flex-col h-full z-10 flex-shrink-0 transition-colors">
            <!-- Logo area -->
            <div class="p-6 border-b border-card-border dark:border-dark-border flex items-center h-[88px] gap-2">
                <h1 class="text-xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                <span class="bg-primary/10 text-primary text-[8px] font-bold px-2 py-0.5 rounded-full uppercase ml-1">Admin Panel</span>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 py-6 space-y-2 px-4 overflow-y-auto w-full">
                <a href="dashboard.php"
                    class="flex items-center gap-3 px-4 py-3 bg-primary/5 dark:bg-primary/10 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="products.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="package" class="w-5 h-5"></i>
                    <span>Products</span>
                </a>
                <a href="orders.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                    <span>Orders</span>
                </a>
                <a href="users.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
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

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto p-4 md:p-8 w-full bg-app-bg dark:bg-dark-bg pb-28 md:pb-8 transition-colors">

                <!-- Page Header Info -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">Dashboard</h2>
                        <p class="text-sm text-gray-400 mt-0.5 font-medium">A bird's eye view of your metrics.</p>
                    </div>
                    <div class="hidden sm:block">
                        <div class="bg-white dark:bg-dark-card border border-card-border dark:border-dark-border rounded-xl px-4 py-2 flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-gray-300 shadow-sm transition-colors">
                            <i data-lucide="calendar" class="w-4 h-4 text-primary"></i>
                            <?php echo date('M d, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Stat Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <!-- Total Orders -->
                    <div class="bg-white dark:bg-dark-card p-5 rounded-2xl border border-card-border dark:border-dark-border flex flex-col justify-between h-[130px] md:h-[150px] shadow-sm transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-50 dark:bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-500 flex-shrink-0">
                                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                            </div>
                            <span class="text-[9px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Orders</span>
                        </div>
                        <p class="text-2xl font-black text-gray-900 dark:text-white transition-colors"><?php echo number_format($stats['total_orders']); ?></p>
                    </div>

                    <!-- Total Sales -->
                    <div class="bg-white dark:bg-dark-card p-5 rounded-2xl border border-card-border dark:border-dark-border flex flex-col justify-between h-[130px] md:h-[150px] shadow-sm transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-50 dark:bg-green-500/10 rounded-xl flex items-center justify-center text-green-500 flex-shrink-0">
                                <i data-lucide="dollar-sign" class="w-5 h-5"></i>
                            </div>
                            <span class="text-[9px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Sales</span>
                        </div>
                        <p class="text-xl md:text-2xl font-black text-gray-900 dark:text-white transition-colors truncate">Rs. <?php echo number_format($stats['total_sales'], 0); ?></p>
                    </div>

                    <!-- Active Users -->
                    <div class="bg-white dark:bg-dark-card p-5 rounded-2xl border border-card-border dark:border-dark-border flex flex-col justify-between h-[130px] md:h-[150px] shadow-sm transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-50 dark:bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-500 flex-shrink-0">
                                <i data-lucide="users" class="w-5 h-5"></i>
                            </div>
                            <span class="text-[9px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Users</span>
                        </div>
                        <p class="text-2xl font-black text-gray-900 dark:text-white transition-colors"><?php echo number_format($stats['total_users']); ?></p>
                    </div>

                    <!-- Low Stock -->
                    <div class="bg-white dark:bg-dark-card p-5 rounded-2xl border border-card-border dark:border-dark-border flex flex-col justify-between h-[130px] md:h-[150px] shadow-sm transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-50 dark:bg-red-500/10 rounded-xl flex items-center justify-center text-red-500 flex-shrink-0">
                                <i data-lucide="package" class="w-5 h-5"></i>
                            </div>
                            <span class="text-[9px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Low Stock</span>
                        </div>
                        <p class="text-2xl font-black text-gray-900 dark:text-white transition-colors"><?php echo $stats['low_stock']; ?></p>
                    </div>
                </div>

                <!-- Main Dashboard Sections -->
                <div class="space-y-8">
                    
                    <!-- Row 1: Orders (8/12) & Feedback (4/12) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        
                        <!-- Recent Orders -->
                        <div class="lg:col-span-8 flex flex-col h-[550px]">
                            <div class="flex justify-between items-center mb-4 px-2">
                                <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight uppercase italic transition-colors">Recent Orders</h3>
                                <a href="orders.php" class="text-[10px] font-bold text-primary hover:text-primary-hover flex items-center gap-1 transition-all uppercase tracking-widest">
                                    Manage Orders <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                            
                            <div class="flex-1 bg-white dark:bg-dark-card rounded-[2.5rem] shadow-xl dark:shadow-none shadow-gray-200/50 border border-card-border dark:border-dark-border p-6 overflow-hidden flex flex-col transition-colors">
                                <div class="overflow-x-auto custom-scrollbar flex-1">
                                    <table class="w-full text-left whitespace-nowrap min-w-[600px]">
                                        <thead>
                                            <tr class="border-b border-card-border dark:border-dark-border">
                                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">ORDER ID</th>
                                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">CUSTOMER</th>
                                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">AMOUNT</th>
                                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em] text-center">STATUS</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-card-border dark:divide-dark-border">
                                            <?php if (empty($recent_orders)): ?>
                                                <tr><td colspan="4" class="py-10 text-center text-gray-400 dark:text-gray-500 font-medium italic">No orders yet.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($recent_orders as $order): 
                                                    $status_class = match($order['status']) {
                                                        'delivered'       => 'bg-green-50 text-green-600 border-green-200',
                                                        'cancelled'       => 'bg-red-50 text-red-600 border-red-200',
                                                        default           => 'bg-orange-50 text-orange-500 border-orange-200',
                                                    };
                                                ?>
                                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition-colors group">
                                                        <td class="py-4"><span class="font-bold text-primary text-sm">#FE-<?php echo $order['id']; ?></span></td>
                                                        <td class="py-4">
                                                            <div class="font-black text-gray-900 dark:text-white text-xs tracking-tight transition-colors"><?php echo sanitize($order['full_name']); ?></div>
                                                            <div class="text-[10px] text-gray-400 dark:text-gray-500 font-medium transition-colors"><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></div>
                                                        </td>
                                                        <td class="py-4"><span class="font-black text-gray-900 dark:text-white text-xs transition-colors">Rs. <?php echo number_format($order['total_price'], 0); ?></span></td>
                                                        <td class="py-4 text-center">
                                                            <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-[0.1em] border <?php echo $status_class; ?> dark:bg-opacity-10">
                                                                <?php echo str_replace('_', ' ', $order['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Feedback -->
                        <div class="lg:col-span-4 flex flex-col h-[550px]">
                            <div class="flex justify-between items-center mb-4 px-2">
                                <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight uppercase italic transition-colors">Customer Feedback</h3>
                                <i data-lucide="heart" class="w-4 h-4 text-secondary fill-secondary/10"></i>
                            </div>

                            <div class="flex-1 bg-white dark:bg-dark-card rounded-[2.5rem] shadow-xl dark:shadow-none shadow-gray-200/50 border border-card-border dark:border-dark-border p-6 overflow-y-auto custom-scrollbar transition-colors">
                                <div class="space-y-4">
                                    <?php if (empty($recent_feedback)): ?>
                                        <div class="text-center py-20 opacity-30 italic text-sm text-gray-900 dark:text-white">No feedback received.</div>
                                    <?php else: ?>
                                        <?php foreach ($recent_feedback as $fb): ?>
                                            <div class="p-4 bg-section-bg dark:bg-dark-bg rounded-2xl border border-primary/5 dark:border-dark-border transition-all hover:border-primary/20">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <div class="w-8 h-8 rounded-lg overflow-hidden border border-card-border">
                                                        <img src="<?php echo $fb['profile_pic'] ? '../'.$fb['profile_pic'] : '../images/default-avatar.png'; ?>" class="w-full h-full object-cover">
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <h4 class="font-bold text-gray-900 dark:text-white text-[11px] truncate uppercase tracking-tighter transition-colors"><?php echo sanitize($fb['full_name']); ?></h4>
                                                        <div class="flex text-yellow-500 gap-0.5">
                                                            <?php for($i=0; $i<$fb['rating']; $i++) echo '<i data-lucide="star" class="w-2.5 h-2.5 fill-current"></i>'; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed font-medium transition-colors">"<?php echo sanitize($fb['feedback_text']); ?>"</p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Row 2: Product Reviews (Horizontal Slider) -->
                    <div class="flex flex-col">
                        <div class="flex justify-between items-center mb-4 px-2">
                            <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight uppercase italic transition-colors">Product Reviews</h3>
                            <div class="flex gap-2">
                                <span class="bg-primary hover:bg-primary-hover w-6 h-6 rounded-full flex items-center justify-center text-white shadow-lg cursor-pointer transition-all" onclick="document.getElementById('reviewSlider').scrollBy({left: -200, behavior: 'smooth'})">
                                    <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                                </span>
                                <span class="bg-primary hover:bg-primary-hover w-6 h-6 rounded-full flex items-center justify-center text-white shadow-lg cursor-pointer transition-all" onclick="document.getElementById('reviewSlider').scrollBy({left: 200, behavior: 'smooth'})">
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                        </div>

                        <div id="reviewSlider" class="horizontal-slider">
                            <?php if (empty($recent_reviews)): ?>
                                <div class="w-full text-center py-10 glass-card rounded-[2.5rem] opacity-30 italic text-sm text-gray-900 dark:text-white">No product reviews yet.</div>
                            <?php else: ?>
                                <?php foreach ($recent_reviews as $rev): ?>
                                    <div class="review-card bg-white dark:bg-dark-card rounded-[2.5rem] p-6 shadow-xl dark:shadow-none shadow-gray-200/50 border border-card-border dark:border-dark-border flex flex-col transition-all hover:shadow-2xl hover:shadow-primary/5 hover:border-primary/10 transition-colors">
                                        <div class="flex items-center gap-4 mb-4">
                                            <div class="w-12 h-12 rounded-2xl overflow-hidden border border-card-border flex-shrink-0">
                                                <img src="<?php echo $rev['profile_pic'] ? '../'.$rev['profile_pic'] : '../images/default-avatar.png'; ?>" class="w-full h-full object-cover">
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] transition-colors">Reviewed</h3>
                                                <h4 class="font-black text-gray-900 dark:text-white text-sm truncate uppercase tracking-tighter leading-tight transition-colors"><?php echo sanitize($rev['product_name']); ?></h4>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex text-yellow-500 gap-0.5 mb-3">
                                                <?php for($i=0; $i<$rev['rating']; $i++) echo '<i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>'; ?>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed font-medium line-clamp-3 italic transition-colors">
                                                "<?php echo sanitize($rev['review_text']); ?>"
                                            </p>
                                        </div>
                                        <div class="mt-4 pt-4 border-t border-card-border flex justify-between items-center">
                                            <span class="text-[10px] font-black text-gray-900 dark:text-white uppercase transition-colors"><?php echo sanitize($rev['full_name']); ?></span>
                                            <span class="text-[9px] font-bold text-gray-300 uppercase"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Navigation (if needed) -->
    <?php include 'includes/bottom_nav.php'; ?>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>