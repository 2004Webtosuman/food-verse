<?php
// delivery/dashboard.php — Unified FoodVerse Rider UI (Admin Harmony)
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_delivery()) {
    $_SESSION['flash_message'] = "Access denied. Delivery role required.";
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Rider';

// STATUS FILTER LOGIC
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Statistics Extraction (Dynamic)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE delivery_user_id = ? AND status = 'delivered'");
$stmt->execute([$user_id]);
$completed_count = $stmt->fetchColumn();

// Fetch available orders for stats count
$stmt_avail_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'confirmed' AND delivery_user_id IS NULL");
$stmt_avail_count->execute();
$available_count = $stmt_avail_count->fetchColumn();

// UNIFIED MISSION FETCHING LOGIC
$query = "SELECT o.*, u.full_name as customer_name, u.address as customer_address
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE ";

$params = [];

if ($filter_status === 'available') {
    // Only broadcoast missions
    $query .= "o.status = 'confirmed' AND o.delivery_user_id IS NULL";
} else if ($filter_status === 'all') {
    // Both broadcasted missions AND assigned missions (not delivered/cancelled)
    $query .= "((o.status = 'confirmed' AND o.delivery_user_id IS NULL) OR (o.delivery_user_id = ? AND o.status NOT IN ('delivered', 'cancelled')))";
    $params[] = $user_id;
} else {
    // Specific status assigned to this rider
    $query .= "o.delivery_user_id = ? AND o.status = ?";
    $params[] = $user_id;
    $params[] = $filter_status;
}

$query .= " ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$missions = $stmt->fetchAll();

// Legit Earning Logic (Based on prefixed Rs. 50 charge)
$total_earnings = $completed_count * 50.00;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Rider Dashboard - FoodVerse</title>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 107, 53, 0.2); border-radius: 10px; }
    </style>
</head>
<body class="bg-app-bg min-h-screen">

    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar (Admin Symmetry) -->
        <aside class="w-64 bg-white border-r border-card-border hidden md:flex flex-col h-full z-10 flex-shrink-0">
            <div class="p-6 border-b border-card-border flex items-center h-[88px] gap-2">
                <h1 class="text-xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                <span class="bg-primary/10 text-primary text-[8px] font-bold px-2 py-0.5 rounded-full uppercase ml-1">Rider Hub</span>
            </div>

            <nav class="flex-1 py-6 space-y-2 px-4 overflow-y-auto w-full">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-primary/5 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="package" class="w-5 h-5"></i>
                    <span>My Deliveries</span>
                </a>
                <a href="earnings.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                    <span>Earnings</span>
                </a>
                <a href="reviews.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="star" class="w-5 h-5"></i>
                    <span>Reviews</span>
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                    <span>Verification</span>
                </a>
            </nav>

            <div class="p-4 mt-auto">
                <button onclick="startGPS()" class="w-full py-4 bg-primary text-white text-[10px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary/20 hover:bg-primary-hover transition-all flex items-center justify-center gap-2">
                    <i data-lucide="radio" class="w-4 h-4 animate-pulse"></i>
                    Go Online
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header class="bg-white border-b border-card-border h-[72px] md:h-[88px] flex items-center justify-between md:justify-end px-6 md:px-8 z-10 flex-shrink-0">
                <div class="md:hidden flex items-center gap-2">
                    <h1 class="text-2xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                </div>
                <div class="flex items-center gap-4 md:gap-6">
                    <div class="flex items-center gap-4 text-gray-500">
                        <a href="notifications.php" class="relative group">
                            <i data-lucide="bell" class="w-6 h-6 hover:text-primary cursor-pointer transition-colors"></i>
                            <span id="notif-badge" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[8px] font-black rounded-full flex items-center justify-center opacity-0 transition-opacity">0</span>
                        </a>
                        <a href="settings.php" class="hover:text-primary transition-colors">
                            <i data-lucide="settings" class="w-6 h-6 cursor-pointer"></i>
                        </a>
                    </div>
                    <div class="h-10 w-px bg-gray-100 hidden sm:block"></div>
                    <a href="../logout.php" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium transition-all">
                        <i data-lucide="power" class="w-5 h-5"></i>
                        <span class="hidden md:inline">Logout</span>
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 md:p-8 w-full bg-app-bg pb-28 md:pb-8">
                
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-gray-900 tracking-tight">Active Duty</h2>
                        <p class="text-sm text-gray-400 mt-0.5 font-medium italic">Welcome back, <?php echo htmlspecialchars($user_name); ?></p>
                    </div>
                    <div class="flex gap-4">
                        <div class="bg-white border border-card-border rounded-2xl px-5 py-3 flex items-center gap-3 shadow-sm">
                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-[10px] font-black text-gray-900 uppercase tracking-widest">GPS Active</span>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid (Admin Symmetry) -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
                    <div class="bg-white p-6 rounded-[2rem] border border-card-border flex flex-col justify-between h-[150px] shadow-sm relative overflow-hidden group">
                        <div class="flex items-center gap-3 relative z-10">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-500 flex-shrink-0"><i data-lucide="package" class="w-5 h-5"></i></div>
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Active</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900 relative z-10"><?php echo count($missions); ?></p>
                        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-blue-500/5 rounded-full group-hover:scale-150 transition-all duration-700"></div>
                    </div>

                    <div class="bg-white p-6 rounded-[2rem] border border-card-border flex flex-col justify-between h-[150px] shadow-sm relative overflow-hidden group">
                        <div class="flex items-center gap-3 relative z-10">
                            <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center text-green-500 flex-shrink-0"><i data-lucide="banknote" class="w-5 h-5"></i></div>
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Earned</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900 relative z-10">Rs. <?php echo number_format($total_earnings, 0); ?></p>
                        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-green-500/5 rounded-full group-hover:scale-150 transition-all duration-700"></div>
                    </div>

                    <div class="bg-white p-6 rounded-[2rem] border border-card-border flex flex-col justify-between h-[150px] shadow-sm relative overflow-hidden group">
                        <div class="flex items-center gap-3 relative z-10">
                            <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center text-purple-500 flex-shrink-0"><i data-lucide="check-circle" class="w-5 h-5"></i></div>
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Done</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900 relative z-10"><?php echo $completed_count; ?></p>
                        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-purple-500/5 rounded-full group-hover:scale-150 transition-all duration-700"></div>
                    </div>

                    <div class="bg-white p-6 rounded-[2rem] border border-card-border flex flex-col justify-between h-[150px] shadow-sm relative overflow-hidden group">
                        <div class="flex items-center gap-3 relative z-10">
                            <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary flex-shrink-0"><i data-lucide="bell" class="w-5 h-5"></i></div>
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Requests</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900 relative z-10"><?php echo $available_count; ?></p>
                        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-primary/5 rounded-full group-hover:scale-150 transition-all duration-700"></div>
                    </div>
                </div>

                <!-- MISSION FILTERS -->
                <div class="flex flex-wrap gap-2 md:gap-4 mb-8">
                    <?php 
                    $filters = [
                        'all' => 'All',
                        'available' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'preparing' => 'Preparing',
                        'out_for_delivery' => 'Out Delivery',
                        'delivered' => 'Delivered'
                    ];
                    foreach ($filters as $key => $label): 
                        $active = ($filter_status === $key);
                    ?>
                        <a href="?status=<?php echo $key; ?>" class="px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest transition-all border <?php echo $active ? 'bg-primary text-white border-primary shadow-lg shadow-primary/20' : 'bg-white text-gray-400 border-card-border hover:border-primary/40 hover:text-primary'; ?>">
                            <?php echo $label; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- MISSION CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (empty($missions)): ?>
                        <div class="col-span-full py-20 bg-white rounded-[2.5rem] border border-card-border text-center">
                            <div class="w-16 h-16 bg-gray-50 text-gray-200 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="package-search" class="w-8 h-8"></i>
                            </div>
                            <p class="text-sm font-bold text-gray-400 italic">No missions found matching this criteria.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($missions as $m): 
                            $is_available = ($m['delivery_user_id'] === null);
                            $status_class = match($m['status']) {
                                'delivered'       => 'bg-green-50 text-green-600 border-green-200',
                                'out_for_delivery'=> 'bg-blue-50 text-blue-500 border-blue-200',
                                'confirmed'       => 'bg-orange-50 text-orange-500 border-orange-200',
                                default           => 'bg-gray-50 text-gray-500 border-gray-200',
                            };
                        ?>
                            <div class="bg-white p-8 rounded-[2.5rem] border border-card-border shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group relative overflow-hidden">
                                <div class="absolute right-0 top-0 w-32 h-32 bg-primary/5 rounded-bl-[5rem] translate-x-10 -translate-y-10 group-hover:translate-x-8 group-hover:-translate-y-8 transition-all duration-700"></div>
                                
                                <div class="flex justify-between items-start mb-6 relative z-10">
                                    <div class="flex gap-4 items-center">
                                        <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary">
                                            <i data-lucide="package" class="w-6 h-6"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xl font-black text-gray-900 uppercase italic">#FE-<?php echo $m['id']; ?></h4>
                                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest"><?php echo date('M d, Y', strtotime($m['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xl font-black text-primary leading-none italic">Rs. <?php echo number_format($m['total_price'], 0); ?></p>
                                    </div>
                                </div>

                                <div class="space-y-4 mb-8 relative z-10">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400"><i data-lucide="user" class="w-4 h-4"></i></div>
                                        <p class="text-[11px] font-black text-gray-600 uppercase tracking-tight"><?php echo sanitize($m['customer_name']); ?></p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400"><i data-lucide="map-pin" class="w-4 h-4"></i></div>
                                        <p class="text-[11px] font-bold text-gray-400 italic line-clamp-1"><?php echo sanitize($m['customer_address']); ?></p>
                                    </div>
                                    <div class="pt-2">
                                        <span class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest border <?php echo $status_class; ?>">
                                            <?php echo str_replace('_', ' ', $m['status']); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="flex gap-3 relative z-10">
                                    <?php if ($is_available): ?>
                                        <a href="view_order.php?id=<?php echo $m['id']; ?>" class="flex-1 py-4 bg-gray-50 border border-card-border text-gray-900 font-bold rounded-2xl flex items-center justify-center text-[10px] uppercase tracking-widest hover:bg-gray-100 transition-all">Review</a>
                                        <form action="actions/accept_order.php" method="POST" class="flex-1">
                                            <input type="hidden" name="order_id" value="<?php echo $m['id']; ?>">
                                            <button type="submit" class="w-full h-full py-4 bg-primary text-white font-black rounded-2xl flex items-center justify-center text-[10px] uppercase tracking-widest shadow-lg shadow-primary/20 hover:bg-primary-hover transition-all">Accept</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="view_order.php?id=<?php echo $m['id']; ?>" class="w-full py-4 bg-gray-900 text-white font-black rounded-2xl flex items-center justify-center gap-3 text-[10px] uppercase tracking-widest shadow-xl shadow-gray-900/20 hover:bg-black transition-all">
                                            <i data-lucide="navigation" class="w-4 h-4 text-primary"></i> Launch HUD
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script>
        lucide.createIcons();
        function startGPS() {
            alert("Rider GPS Synchronized with FoodVerse Mainframe.");
        }

        // Notification Engine Polling
        let seenNotifs = new Set();
        async function pollNotifications() {
            try {
                const response = await fetch('api/get_notifications.php');
                const data = await response.json();
                
                if (data.success) {
                    const badge = document.getElementById('notif-badge');
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.classList.remove('opacity-0');
                    } else {
                        badge.classList.add('opacity-0');
                    }

                    // Show popups for new notifications
                    if (data.notifications && data.notifications.length > 0) {
                        data.notifications.forEach(notif => {
                            if (!seenNotifs.has(notif.id)) {
                                seenNotifs.add(notif.id);
                                showNotifToast(notif);
                            }
                        });
                    }
                }
            } catch (err) {
                console.error('Notification Polling Error:', err);
            }
        }

        function showNotifToast(notif) {
            // Check if container exists, if not create it
            let container = document.getElementById('notif-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'notif-toast-container';
                container.className = 'fixed top-4 left-0 right-0 z-[100] flex flex-col flex-col-reverse items-center gap-3 pointer-events-none';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'bg-gray-900 border border-card-border p-4 rounded-3xl shadow-2xl flex items-center gap-4 w-[90%] max-w-sm pointer-events-auto transform -translate-y-10 opacity-0 transition-all duration-500 shadow-primary/20 border-primary/20';
            toast.innerHTML = `
                <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center shrink-0">
                    <i data-lucide="bell" class="w-6 h-6 text-primary"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-black text-white text-[12px] uppercase tracking-widest leading-tight truncate mb-1">${notif.title}</h4>
                    <p class="text-[11px] text-gray-400 font-bold leading-tight line-clamp-2">${notif.message}</p>
                </div>
                ${notif.link ? `<a href="${notif.link}" class="w-10 h-10 bg-primary hover:bg-primary-hover rounded-xl flex items-center justify-center shrink-0 transition-all shadow-lg active:scale-95"><i data-lucide="arrow-right" class="w-5 h-5 text-white"></i></a>` : ''}
            `;

            // Prepend so newest is at the bottom of the stack (natural mobile feel)
            container.prepend(toast);
            lucide.createIcons({ root: toast });

            // Animate in
            requestAnimationFrame(() => {
                toast.classList.remove('-translate-y-10', 'opacity-0');
            });

            // Play sound optionally (if browser allows)
            if ('vibrate' in navigator) {
                navigator.vibrate([200, 100, 200]);
            }

            // Remove after 10 seconds
            setTimeout(() => {
                toast.classList.add('opacity-0', 'scale-90');
                setTimeout(() => toast.remove(), 500);
            }, 10000);
        }

        // Start background worker for location tracking and unassigned order broadcast expansion
        setInterval(pollNotifications, 5000); // 5 seconds polling rate for faster dispatching
        pollNotifications(); // Run once on load
    </script>
</body>
</html>
