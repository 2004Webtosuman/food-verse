<?php
// admin/orders.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_admin()) redirect('../login.php');

$orders = [];
try {
    $stmt = $pdo->query("SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}
?>
<head>
    <?php include 'includes/head.php'; ?>
    <title>Manage Orders - FoodVerse</title>
    <style>
        /* Filter pill active */
        .filter-btn.active {
            background: #FF6B35;
            color: #fff;
            border-color: #FF6B35;
        }
        .filter-btn {
            border: 1.5px solid #F1EAE4;
            background: #fff;
            color: #888;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 0.05em;
            border-radius: 9999px;
            padding: 6px 16px;
            cursor: pointer;
            transition: all .15s;
            white-space: nowrap;
        }
        .filter-btn:hover { border-color: #FF6B35; color: #FF6B35; }
        
        .dark .filter-btn {
            background: #16191F;
            border-color: #1F242C;
            color: #8E95A1;
        }
        .dark .filter-btn.active {
            background: #FF6B35;
            color: #fff;
            border-color: #FF6B35;
        }

        /* Status badge colours */
        .badge-pending      { background:#FFF3EC; color:#FF6B35; border-color:#FFD9C5; }
        .badge-confirmed    { background:#FFF3EC; color:#FF8C42; border-color:#FFD9C5; }
        .badge-preparing    { background:#FFFBEC; color:#D97706; border-color:#FDE68A; }
        .badge-shipping     { background:#EFF6FF; color:#3B82F6; border-color:#BFDBFE; }
        .badge-delivered    { background:#ECFDF5; color:#059669; border-color:#A7F3D0; }
        .badge-cancelled    { background:#FEF2F2; color:#DC2626; border-color:#FECACA; }
        .badge-awaiting     { background:#F5F3FF; color:#7C3AED; border-color:#DDD6FE; }

        .badge-paid         { background:#ECFDF5; color:#059669; border-color:#A7F3D0; }
        .badge-unpaid       { background:#FFF3EC; color:#FF6B35; border-color:#FFD9C5; }
    </style>
</head>
<body class="bg-app-bg dark:bg-dark-bg min-h-screen text-gray-900 dark:text-gray-100 transition-colors">

    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-dark-card border-r border-card-border dark:border-dark-border hidden md:flex flex-col h-full z-10 flex-shrink-0 transition-colors">
            <div class="p-6 border-b border-card-border dark:border-dark-border flex items-center h-[88px] gap-2">
                <h1 class="text-xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                <span class="bg-primary/10 text-primary text-[8px] font-bold px-2 py-0.5 rounded-full uppercase ml-1">Admin Panel</span>
            </div>
            <nav class="flex-1 py-6 space-y-2 px-4 overflow-y-auto w-full">
                <a href="dashboard.php"  class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>Dashboard</span>
                </a>
                <a href="products.php"   class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="package" class="w-5 h-5"></i><span>Products</span>
                </a>
                <a href="orders.php"     class="flex items-center gap-3 px-4 py-3 bg-primary/5 dark:bg-primary/10 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
                    <i data-lucide="shopping-bag" class="w-5 h-5"></i><span>Orders</span>
                </a>
                <a href="users.php"      class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="users" class="w-5 h-5"></i><span>Users</span>
                </a>
                <a href="categories.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="tags" class="w-5 h-5"></i><span>Categories</span>
                </a>
                <a href="reports.php"    class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="bar-chart-2" class="w-5 h-5"></i><span>Reports</span>
                </a>
            </nav>
        </aside>

        <!-- Main content wrapper -->
        <div class="flex-1 flex flex-col h-full overflow-hidden relative">

            <!-- Topbar -->
            <?php include 'includes/topbar.php'; ?>

            <!-- Scrollable main area -->
            <main class="flex-1 overflow-y-auto p-4 md:p-8 w-full bg-app-bg dark:bg-dark-bg pb-28 md:pb-8 transition-colors">
                <?php display_flash_message(); ?>

                <!-- Page heading -->
                <div class="mb-5">
                    <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">Active Orders</h2>
                    <p class="text-sm text-gray-400 mt-0.5 font-medium">Monitor and fulfill customer requests in real-time.</p>
                </div>

                <!-- Filter pills — wrap on mobile, no horizontal scroll -->
                <div class="flex flex-wrap gap-2 mb-5" id="filter-bar">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="pending">Pending</button>
                    <button class="filter-btn" data-filter="confirmed">Confirmed</button>
                    <button class="filter-btn" data-filter="preparing">Preparing</button>
                    <button class="filter-btn" data-filter="out_for_delivery">Shipping</button>
                    <button class="filter-btn" data-filter="delivered">Delivered</button>
                </div>

                <!-- ── DESKTOP TABLE ── (hidden on mobile) -->
                <div class="hidden md:block bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-6 md:p-8 w-full transition-colors">
                    <table class="w-full text-left whitespace-nowrap min-w-[800px]">
                        <thead>
                            <tr class="border-b border-card-border dark:border-dark-border">
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">ORDER ID</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">CUSTOMER</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">AMOUNT</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em] text-center">STATUS</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em] text-center">PAYMENT</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em] text-right">DATE</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em] text-right">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-card-border dark:divide-dark-border" id="desktop-tbody">
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7" class="py-10 text-center text-gray-400 font-medium">No orders found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o):
                                $paymentStatus = strtoupper($o['payment_status']);
                                $paymentClass  = $paymentStatus === 'PAID' ? 'bg-green-50 text-green-600 border-green-200' : 'bg-primary/10 text-primary border-primary/20';
                                $statusClass = match($o['status']) {
                                    'delivered'       => 'bg-green-50 text-green-600 border-green-200',
                                    'out_for_delivery'=> 'bg-blue-50 text-blue-500 border-blue-200',
                                    'preparing'       => 'bg-yellow-50 text-yellow-600 border-yellow-200',
                                    'confirmed'       => 'bg-orange-50 text-orange-500 border-orange-200',
                                    'cancelled'       => 'bg-red-50 text-red-600 border-red-200',
                                    default           => 'bg-orange-50 text-orange-500 border-orange-200',
                                };
                            ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition-colors group order-row" data-status="<?php echo $o['status']; ?>">
                                <td class="py-5"><span class="font-bold text-primary text-sm">#FE-<?php echo $o['id']; ?></span></td>
                                <td class="py-5">
                                    <div class="font-bold text-gray-900 dark:text-white text-sm transition-colors"><?php echo sanitize($o['full_name']); ?></div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 font-medium lowercase"><?php echo sanitize($o['email'] ?? ''); ?></div>
                                </td>
                                <td class="py-5"><span class="font-black text-gray-900 dark:text-white transition-colors">Rs. <?php echo number_format($o['total_price'], 0); ?></span></td>
                                <td class="py-5 text-center">
                                    <form action="actions/update_order_status.php" method="POST" class="inline">
                                        <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                        <select name="status" onchange="this.form.submit()"
                                            class="border rounded-md px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.1em] focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer <?php echo $statusClass; ?>">
                                            <option value="pending"           <?php echo $o['status']=='pending'           ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed"         <?php echo $o['status']=='confirmed'         ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="preparing"         <?php echo $o['status']=='preparing'         ? 'selected' : ''; ?>>Preparing</option>
                                            <option value="out_for_delivery"  <?php echo $o['status']=='out_for_delivery'  ? 'selected' : ''; ?>>Out for Delivery</option>
                                            <option value="delivered"         <?php echo $o['status']=='delivered'         ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled"         <?php echo $o['status']=='cancelled'         ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="py-5 text-center">
                                    <span class="px-3 py-1.5 rounded-md text-[10px] font-bold uppercase tracking-[0.1em] border <?php echo $paymentClass; ?>"><?php echo $paymentStatus; ?></span>
                                </td>
                                <td class="py-5 text-right text-gray-500 text-xs font-medium"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td>
                                <td class="py-5 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="view_order.php?id=<?php echo $o['id']; ?>" class="p-2 text-gray-400 hover:text-primary bg-gray-50 dark:bg-dark-bg rounded-lg border border-transparent hover:border-primary/20 hover:bg-primary/5 transition-colors">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        <?php if ($paymentStatus === 'UNPAID'): ?>
                                        <button onclick="confirmDeleteOrder(<?php echo $o['id']; ?>)" class="p-2 text-gray-400 hover:text-red-500 bg-gray-50 dark:bg-dark-bg rounded-lg border border-transparent hover:border-red-200 hover:bg-red-50 transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ── MOBILE CARDS ── (hidden on desktop) -->
                <div class="md:hidden space-y-3" id="mobile-cards">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-10 text-gray-400 font-medium">No orders found.</div>
                <?php else: ?>
                    <?php foreach ($orders as $o):
                        $paymentStatus = strtoupper($o['payment_status']);
                        $payBadge = $paymentStatus === 'PAID' ? 'badge-paid' : 'badge-unpaid';
                        $statusLabel = match($o['status']) {
                            'pending'          => 'Pending',
                            'confirmed'        => 'Confirmed',
                            'preparing'        => 'Preparing',
                            'out_for_delivery' => 'Shipping',
                            'delivered'        => 'Delivered',
                            'cancelled'        => 'Cancelled',
                            default            => ucfirst($o['status']),
                        };
                        $statusBadge = match($o['status']) {
                            'pending'          => 'badge-pending',
                            'confirmed'        => 'badge-confirmed',
                            'preparing'        => 'badge-preparing',
                            'out_for_delivery' => 'badge-shipping',
                            'delivered'        => 'badge-delivered',
                            'cancelled'        => 'badge-cancelled',
                            default            => 'badge-pending',
                        };
                        $selectClass = match($o['status']) {
                            'delivered'        => 'bg-green-50 text-green-600 border-green-200 dark:bg-green-500/10 dark:text-green-500 dark:border-green-500/30',
                            'out_for_delivery' => 'bg-blue-50 text-blue-500 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/30',
                            'preparing'        => 'bg-yellow-50 text-yellow-600 border-yellow-200 dark:bg-yellow-500/10 dark:text-yellow-500 dark:border-yellow-500/30',
                            'confirmed'        => 'bg-orange-50 text-orange-500 border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/30',
                            'cancelled'        => 'bg-red-50 text-red-600 border-red-200 dark:bg-red-500/10 dark:text-red-500 dark:border-red-500/30',
                            default            => 'bg-orange-50 text-orange-500 border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/30',
                        };
                    ?>
                    <div class="order-row bg-white dark:bg-dark-card rounded-2xl border border-card-border dark:border-dark-border p-4 shadow-sm transition-colors" data-status="<?php echo $o['status']; ?>">

                        <!-- Row 1: icon + info + amount/date -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <!-- Package icon -->
                                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i data-lucide="package" class="w-5 h-5 text-primary"></i>
                                </div>
                                <div>
                                    <span class="text-primary font-bold text-sm">#FE-<?php echo $o['id']; ?></span>
                                    <div class="font-bold text-gray-900 dark:text-white text-sm leading-tight transition-colors"><?php echo sanitize($o['full_name']); ?></div>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border <?php echo $payBadge; ?>"><?php echo $paymentStatus; ?></span>
                                </div>
                            </div>
                            <!-- Amount + date -->
                            <div class="text-right flex-shrink-0">
                                <div class="font-black text-primary text-base">Rs. <?php echo number_format($o['total_price'], 0); ?></div>
                                <div class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold uppercase mt-0.5 transition-colors"><?php echo strtoupper(date('M d', strtotime($o['created_at']))); ?></div>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-card-border dark:border-dark-border my-3 transition-colors"></div>

                        <!-- Row 2: status dropdown + actions -->
                        <div class="flex items-center justify-between gap-3">
                            <!-- Status select -->
                            <form action="actions/update_order_status.php" method="POST" class="flex-1">
                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                <select name="status" onchange="this.form.submit()"
                                    class="w-full border rounded-xl px-3 py-2 text-[11px] font-bold uppercase tracking-wider focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer appearance-none <?php echo $selectClass; ?>">
                                    <option value="pending"          <?php echo $o['status']=='pending'          ? 'selected':''; ?>>Pending</option>
                                    <option value="confirmed"        <?php echo $o['status']=='confirmed'        ? 'selected':''; ?>>Confirmed</option>
                                    <option value="preparing"        <?php echo $o['status']=='preparing'        ? 'selected':''; ?>>Preparing</option>
                                    <option value="out_for_delivery" <?php echo $o['status']=='out_for_delivery' ? 'selected':''; ?>>Out for Delivery</option>
                                    <option value="delivered"        <?php echo $o['status']=='delivered'        ? 'selected':''; ?>>Delivered</option>
                                    <option value="cancelled"        <?php echo $o['status']=='cancelled'        ? 'selected':''; ?>>Cancelled</option>
                                </select>
                            </form>

                            <!-- Action buttons -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="view_order.php?id=<?php echo $o['id']; ?>"
                                   class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border text-gray-400 dark:text-gray-500 hover:text-primary hover:border-primary/30 hover:bg-primary/5 transition-all transition-colors">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <?php if ($paymentStatus === 'UNPAID'): ?>
                                <button onclick="confirmDeleteOrder(<?php echo $o['id']; ?>)"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-50 border border-card-border text-gray-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition-all">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function confirmDeleteOrder(id) {
            if (confirm('Are you sure you want to delete this unpaid order? This action cannot be undone.')) {
                window.location.href = `actions/delete_order.php?id=${id}`;
            }
        }

        // Filter logic — works for both desktop rows and mobile cards
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;
                document.querySelectorAll('.order-row').forEach(row => {
                    const status = row.dataset.status;
                    if (filter === 'all') {
                        row.style.display = '';
                    } else if (filter === 'out_for_delivery') {
                        row.style.display = (status === 'out_for_delivery') ? '' : 'none';
                    } else {
                        row.style.display = (status === filter) ? '' : 'none';
                    }
                });
            });
        });
    </script>

    <?php include 'includes/bottom_nav.php'; ?>
</body>
</html>
