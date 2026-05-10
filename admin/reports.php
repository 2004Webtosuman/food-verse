<?php
// admin/reports.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_admin()) redirect('../login.php');

$netRevenue = 0;
$orderVolume = 0;
$avgBasketSize = 0;
$chartLabels = [];
$chartData = [];

try {
    // 1. Net Revenue (Excluding cancelled)
    $revStmt = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status != 'cancelled'");
    $netRevenue = $revStmt->fetchColumn() ?: 0;

    // 2. Order Volume (Excluding cancelled)
    $volStmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'");
    $orderVolume = $volStmt->fetchColumn() ?: 0;

    // 3. Average Basket Size
    if ($orderVolume > 0) {
        $avgBasketSize = $netRevenue / $orderVolume;
    }

    // 4. Revenue Trajectory (Last 7 Days)
    $dates = [];
    // Pre-fill last 7 days
    for ($i = 6; $i >= 0; $i--) {
        $dateStr = date('Y-m-d', strtotime("-$i days"));
        $dates[$dateStr] = 0;
    }

    $trendStmt = $pdo->query("
        SELECT DATE(created_at) as date, SUM(total_price) as daily_revenue 
        FROM orders 
        WHERE status != 'cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
    ");
    $trendResults = $trendStmt->fetchAll();

    foreach ($trendResults as $row) {
        if (isset($dates[$row['date']])) {
            $dates[$row['date']] = (float)$row['daily_revenue'];
        }
    }

    foreach ($dates as $date => $rev) {
        // Format as 'Apr 06'
        $chartLabels[] = date('M d', strtotime($date));
        $chartData[] = $rev;
    }

    // 5. Top Performance Menu (Best sellers from delivered orders)
    $topStmt = $pdo->query("
        SELECT p.name, p.image_url, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_profit
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'delivered'
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 4
    ");
    $topProducts = $topStmt->fetchAll();

    // 6. System Event Log (Latest orders)
    $eventStmt = $pdo->query("
        SELECT o.id, o.created_at, o.total_price, u.full_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $recentEvents = $eventStmt->fetchAll();

} catch (PDOException $e) {
    // DB error fallback
}

?>
<head>
    <?php include 'includes/head.php'; ?>
    <title>Business Analytics - FoodVerse</title>
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
                <a href="orders.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
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
                <a href="reports.php" class="flex items-center gap-3 px-4 py-3 bg-primary/5 dark:bg-primary/10 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
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

                <!-- Page heading -->
                <div class="mb-6">
                    <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">Business Analytics</h2>
                    <p class="text-sm text-gray-400 mt-0.5 font-medium">Deep dive into performance, revenue, and trends.</p>
                </div>

                <!-- Stat cards — stack on mobile, 3-col on desktop -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <!-- Net Revenue -->
                    <div class="bg-white dark:bg-dark-card rounded-2xl border border-card-border dark:border-dark-border p-6 relative overflow-hidden group transition-colors">
                        <div class="absolute -top-8 -right-8 w-24 h-24 bg-primary/5 rounded-full blur-xl group-hover:bg-primary/10 transition-all duration-500"></div>
                        <h3 class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2 relative z-10">Net Revenue</h3>
                        <p class="text-2xl font-black text-primary relative z-10">Rs. <?php echo number_format($netRevenue); ?></p>
                    </div>
                    <!-- Order Volume -->
                    <div class="bg-white dark:bg-dark-card rounded-2xl border border-card-border dark:border-dark-border p-6 relative overflow-hidden group transition-colors">
                        <div class="absolute -top-8 -right-8 w-24 h-24 bg-secondary/5 rounded-full blur-xl group-hover:bg-secondary/10 transition-all duration-500"></div>
                        <h3 class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2 relative z-10 transition-colors">Order Volume</h3>
                        <p class="text-2xl font-black text-gray-900 dark:text-white relative z-10 transition-colors"><?php echo number_format($orderVolume); ?></p>
                    </div>
                    <!-- Avg Basket -->
                    <div class="bg-white dark:bg-dark-card rounded-2xl border border-card-border dark:border-dark-border p-6 relative overflow-hidden group transition-colors">
                        <div class="absolute -top-8 -right-8 w-24 h-24 bg-accent/5 rounded-full blur-xl group-hover:bg-accent/10 transition-all duration-500"></div>
                        <h3 class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2 relative z-10 transition-colors">Avg Basket Size</h3>
                        <p class="text-2xl font-black text-gray-900 dark:text-white relative z-10 transition-colors">Rs. <?php echo number_format($avgBasketSize); ?></p>
                    </div>
                </div>

                <!-- Revenue chart card -->
                <div class="bg-white dark:bg-dark-card rounded-2xl border border-card-border dark:border-dark-border p-5 md:p-8 w-full mb-6 transition-colors">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight mb-5 transition-colors">Revenue Trajectory</h3>
                    <div class="w-full" style="height:280px">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- New Sections: Top Performance and Event Log -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <!-- Top Performance Menu -->
                    <div class="bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-6 md:p-8 transition-colors">
                        <h3 class="text-xl font-bold mb-8 text-gray-900 dark:text-white flex items-center gap-2 transition-colors">
                            Top Performance Menu
                        </h3>
                        <div class="space-y-8">
                            <?php if (empty($topProducts)): ?>
                                <p class="text-sm text-gray-400 font-medium italic">No delivered products yet.</p>
                            <?php else: ?>
                                <?php foreach ($topProducts as $p): ?>
                                <div class="flex items-center justify-between group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-white dark:bg-dark-bg rounded-2xl overflow-hidden border border-card-border dark:border-dark-border shadow-sm flex-shrink-0 transition-colors">
                                            <img src="../<?php echo !empty($p['image_url']) ? $p['image_url'] : 'images/placeholder.png'; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-900 dark:text-white text-sm transition-colors"><?php echo sanitize($p['name']); ?></h4>
                                            <p class="text-[10px] text-primary font-bold uppercase tracking-widest mt-1">
                                                <?php echo $p['total_sold']; ?> Items Distributed
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-black text-primary text-base">Rs. <?php echo number_format($p['total_profit']); ?></p>
                                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Total Profit Contribution</p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- System Event Log -->
                    <div class="bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-6 md:p-8 transition-colors">
                        <h3 class="text-xl font-bold mb-8 text-gray-900 dark:text-white flex items-center gap-2 transition-colors">
                            System Event Log
                        </h3>
                        <div class="space-y-6">
                            <?php if (empty($recentEvents)): ?>
                                <p class="text-sm text-gray-400 font-medium italic">No recent events recorded.</p>
                            <?php else: ?>
                                <?php foreach ($recentEvents as $e): ?>
                                <div class="flex items-center gap-4 p-4 rounded-2xl border border-gray-50 dark:border-dark-bg hover:border-primary/20 hover:bg-primary/5 transition-all transition-colors">
                                    <div class="w-12 h-12 bg-white dark:bg-dark-bg rounded-full border border-card-border dark:border-dark-border flex items-center justify-center text-primary shadow-sm transition-colors">
                                        <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-sm truncate transition-colors">New Order #<?php echo $e['id']; ?></h4>
                                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest whitespace-nowrap">
                                                <?php echo date('M d, H:i', strtotime($e['created_at'])); ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium transition-colors">Order of Rs. <?php echo number_format($e['total_price']); ?> by <?php echo sanitize($e['full_name']); ?></p>
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

    <script> 
        lucide.createIcons(); 

        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('revenueChart').getContext('2d');
            var isDark = document.documentElement.classList.contains('dark');
            
            // Gradient fill setup
            var gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(255, 107, 53, 0.2)'); // Primary color with opacity
            gradient.addColorStop(1, 'rgba(255, 107, 53, 0.0)');

            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [{
                        label: 'Revenue (Rs.)',
                        data: <?php echo json_encode($chartData); ?>,
                        borderColor: '#FF6B35', // Primary
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#FF6B35',
                        pointBorderColor: isDark ? '#16191F' : '#FFF',
                        pointHoverBackgroundColor: isDark ? '#16191F' : '#FFF',
                        pointHoverBorderColor: '#FF6B35',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4 // Smooth curves
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1A1A1A',
                            titleFont: { family: 'Outfit', size: 12 },
                            bodyFont: { family: 'Outfit', size: 14, weight: 'bold' },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Rs. ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                font: { family: 'Outfit', size: 11, weight: '600' },
                                color: '#9CA3AF'
                            }
                        },
                        y: {
                            grid: {
                                color: isDark ? '#1F242C' : '#F3F4F6', // Very light grid lines
                                drawBorder: false
                            },
                            ticks: {
                                font: { family: 'Outfit', size: 11, weight: '600' },
                                color: '#9CA3AF',
                                callback: function(value) {
                                    return 'Rs. ' + value;
                                },
                                stepSize: 100 // Customize depending on volume
                            },
                            beginAtZero: true
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            });
        });
    </script>
    <?php include 'includes/bottom_nav.php'; ?>
</body>
</html>
