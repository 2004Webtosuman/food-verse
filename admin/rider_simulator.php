<?php
// admin/rider_simulator.php — Admin can assign delivery boys and manage orders
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_admin()) {
    die("Admin access required");
}

// Fetch delivery boys
$riders = $pdo->query("SELECT id, full_name, phone FROM users WHERE role = 'delivery'")->fetchAll();

// Handle assignment or manual update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_rider'])) {
        $order_id = (int)$_POST['order_id'];
        $rider_id = (int)$_POST['rider_id'];
        $pdo->prepare("UPDATE orders SET delivery_user_id = ? WHERE id = ?")->execute([$rider_id, $order_id]);
        $_SESSION['flash_message'] = "Rider assigned to Order #$order_id!";
        redirect('rider_simulator.php');
    }
    if (isset($_POST['update_status'])) {
        $order_id = (int)$_POST['order_id'];
        $new_status = $_POST['new_status'];
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $order_id]);
        $_SESSION['flash_message'] = "Order #$order_id status updated!";
        redirect('rider_simulator.php');
    }
    if (isset($_POST['update_location'])) {
        $order_id = (int)$_POST['order_id'];
        $lat = (float)$_POST['lat'];
        $lng = (float)$_POST['lng'];
        $pdo->prepare("UPDATE orders SET rider_lat = ?, rider_lng = ? WHERE id = ?")->execute([$lat, $lng, $order_id]);
        $_SESSION['flash_message'] = "Rider location for Order #$order_id updated!";
        redirect('rider_simulator.php');
    }
}

$stmt = $pdo->query("SELECT o.id, u.full_name as customer_name, o.status, o.rider_lat, o.rider_lng, o.delivery_user_id,
                            d.full_name as rider_name
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    LEFT JOIN users d ON o.delivery_user_id = d.id
                    WHERE o.status NOT IN ('delivered', 'cancelled')
                    ORDER BY o.created_at DESC");
$active_orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider & Order Manager - FoodVerse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#FF6B35',
                        'primary-hover': '#E85A2A',
                        secondary: '#6C5CE7',
                        accent: '#00C2A8',
                        'app-bg': '#FFF8F2',
                        'section-bg': '#FDF2EC',
                        'card-border': '#F1EAE4',
                    }
                }
            }
        }
    </script>
    <style> body { font-family: 'Outfit', sans-serif; transition: background-color 0.3s, color 0.3s; } </style>
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
                <a href="reports.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-dark-bg hover:text-gray-900 dark:hover:text-white border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="bar-chart-2" class="w-5 h-5"></i>
                    <span>Reports</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header class="bg-white dark:bg-dark-card border-b border-card-border dark:border-dark-border h-[72px] md:h-[88px] flex items-center justify-between md:justify-end px-6 md:px-8 z-10 flex-shrink-0 transition-colors">
                <div class="md:hidden flex items-center gap-2">
                    <h1 class="text-2xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                </div>
                <a href="../logout.php" class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium transition-all transition-colors">
                    <i data-lucide="power" class="w-5 h-5"></i>
                    <span class="hidden md:inline">Logout</span>
                </a>
            </header>

            <main class="flex-1 overflow-y-auto p-4 md:p-8 w-full bg-app-bg dark:bg-dark-bg pb-28 md:pb-8 transition-colors">
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl text-sm font-bold flex items-center gap-3">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
                    </div>
                <?php endif; ?>

                <div class="mb-8">
                    <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">Rider Manager</h2>
                    <p class="text-sm text-gray-400 mt-0.5 font-medium transition-colors">Assign delivery boys and simulate GPS updates.</p>
                </div>

                <!-- Available Riders -->
                <div class="mb-8 p-6 bg-white dark:bg-dark-card rounded-[2rem] shadow-sm border border-card-border dark:border-dark-border transition-colors">
                    <h3 class="text-sm font-black text-gray-900 dark:text-white mb-4 flex items-center gap-2 uppercase tracking-widest transition-colors">
                        <i data-lucide="bike" class="w-4 h-4 text-primary"></i>
                        Active Delivery Boys 
                        <span class="px-2 py-0.5 bg-primary/10 text-primary text-[10px] font-bold rounded-full transition-colors"><?php echo count($riders); ?></span>
                    </h3>
                    <?php if (empty($riders)): ?>
                        <p class="text-xs text-gray-400 font-medium italic">No delivery boys registered.</p>
                    <?php else: ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($riders as $r): ?>
                                <div class="px-3 py-1.5 bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2 transition-colors">
                                    <div class="w-5 h-5 bg-primary/20 rounded-lg text-primary text-[8px] flex items-center justify-center transition-colors"><?php echo strtoupper(substr($r['full_name'], 0, 1)); ?></div>
                                    <?php echo htmlspecialchars($r['full_name']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Active Orders -->
                <div class="space-y-4">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight transition-colors">Active Transmissions</h3>
                    <?php if (empty($active_orders)): ?>
                        <div class="bg-white dark:bg-dark-card p-10 rounded-[2rem] border border-card-border dark:border-dark-border text-center transition-colors">
                            <i data-lucide="satellite" class="w-12 h-12 text-gray-200 dark:text-gray-700 mx-auto mb-4 transition-colors"></i>
                            <p class="text-gray-400 dark:text-gray-500 font-medium transition-colors">No orders available for dispatch simulation.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($active_orders as $order): 
                            $status_class = match($order['status']) {
                                'out_for_delivery'=> 'bg-blue-50 text-blue-500 border-blue-200',
                                'preparing'       => 'bg-yellow-50 text-yellow-600 border-yellow-200',
                                'confirmed', 'pending', 'paid' => 'bg-orange-50 text-orange-500 border-orange-200',
                                default           => 'bg-gray-50 text-gray-600 border-gray-200',
                            };
                        ?>
                            <div class="bg-white dark:bg-dark-card p-6 rounded-[2rem] shadow-sm border border-card-border dark:border-dark-border transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 transition-colors">
                                    <div>
                                        <h4 class="text-lg font-black text-gray-900 dark:text-white transition-colors">Order #<?php echo $order['id']; ?></h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 font-medium transition-colors">Customer: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                        <div class="flex flex-wrap items-center gap-2 mt-2">
                                            <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase tracking-wider border <?php echo $status_class; ?>">
                                                <?php echo str_replace('_', ' ', $order['status']); ?>
                                            </span>
                                            <?php if ($order['rider_name']): ?>
                                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase bg-purple-50 text-purple-500 border border-purple-100 flex items-center gap-1.5">
                                                    <i data-lucide="bike" class="w-3 h-3"></i>
                                                    <?php echo htmlspecialchars($order['rider_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase bg-red-50 text-red-400 border border-red-100 italic">No assigned rider</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 transition-colors">
                                        <button onclick="document.getElementById('sim-form-<?php echo $order['id']; ?>').classList.toggle('hidden')" class="p-2.5 rounded-xl bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border text-gray-500 dark:text-gray-400 hover:text-primary transition-all transition-colors">
                                            <i data-lucide="settings-2" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                </div>

                                <div id="sim-form-<?php echo $order['id']; ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    <!-- Assign Rider -->
                                    <form method="POST" class="p-5 bg-gray-50 dark:bg-dark-bg rounded-2xl border border-card-border dark:border-dark-border transition-colors">
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase mb-3 tracking-widest transition-colors">Assign Pilot</label>
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="rider_id" class="w-full p-3 bg-white dark:bg-dark-card border border-card-border dark:border-dark-border rounded-xl text-sm font-bold mb-3 focus:outline-none focus:ring-2 focus:ring-primary/20 text-gray-900 dark:text-white transition-colors">
                                            <?php foreach ($riders as $r): ?>
                                                <option value="<?php echo $r['id']; ?>" <?php echo $order['delivery_user_id'] == $r['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($r['full_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="assign_rider" class="w-full py-3 bg-primary text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-primary-hover shadow-sm transition-all transition-colors">Assign</button>
                                    </form>

                                    <!-- Update Status -->
                                    <form method="POST" class="p-5 bg-gray-50 dark:bg-dark-bg rounded-2xl border border-card-border dark:border-dark-border transition-colors">
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase mb-3 tracking-widest transition-colors">Update State</label>
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="new_status" class="w-full p-3 bg-white dark:bg-dark-card border border-card-border dark:border-dark-border rounded-xl text-sm font-bold mb-3 focus:outline-none focus:ring-2 focus:ring-secondary/20 text-gray-900 dark:text-white transition-colors">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                            <option value="out_for_delivery" <?php echo $order['status'] == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        </select>
                                        <button type="submit" name="update_status" class="w-full py-3 bg-secondary text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-secondary/90 shadow-sm transition-all transition-colors">Update</button>
                                    </form>

                                    <!-- Simulate GPS -->
                                    <form method="POST" class="p-5 bg-gray-50 dark:bg-dark-bg rounded-2xl border border-card-border dark:border-dark-border transition-colors">
                                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase mb-3 tracking-widest transition-colors">GPS Coordinates</label>
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <div class="grid grid-cols-2 gap-2 mb-3">
                                            <input type="number" name="lat" step="0.000001" value="<?php echo $order['rider_lat'] ?: '27.7200'; ?>" class="p-3 bg-white dark:bg-dark-card border border-card-border dark:border-dark-border rounded-xl text-xs font-bold text-gray-900 dark:text-white transition-colors" placeholder="Lat">
                                            <input type="number" name="lng" step="0.000001" value="<?php echo $order['rider_lng'] ?: '85.3220'; ?>" class="p-3 bg-white dark:bg-dark-card border border-card-border dark:border-dark-border rounded-xl text-xs font-bold text-gray-900 dark:text-white transition-colors" placeholder="Lng">
                                        </div>
                                        <button type="submit" name="update_location" class="w-full py-3 bg-accent text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-accent/90 shadow-sm transition-all mb-3 transition-colors">Update GPS</button>
                                        <div class="flex gap-1.5 transition-colors">
                                            <button type="button" onclick="setGPS(this, 27.7215, 85.3210)" class="flex-1 py-2 bg-white dark:bg-dark-card border border-card-border dark:border-dark-border rounded-lg text-[8px] font-black uppercase text-gray-400 dark:text-gray-500 hover:text-primary hover:border-primary/30 transition-all tracking-tighter transition-colors">Kitchen</button>
                                            <button type="button" onclick="setGPS(this, 27.7190, 85.3225)" class="flex-1 py-2 bg-white dark:bg-dark-card border border-card-border dark:border-dark-border rounded-lg text-[8px] font-black uppercase text-gray-400 dark:text-gray-500 hover:text-primary hover:border-primary/30 transition-all tracking-tighter transition-colors">Route</button>
                                            <button type="button" onclick="setGPS(this, 27.7172, 85.3240)" class="flex-1 py-2 bg-white dark:bg-dark-card border border-card-border dark:border-dark-border rounded-lg text-[8px] font-black uppercase text-gray-400 dark:text-gray-500 hover:text-primary hover:border-primary/30 transition-all tracking-tighter transition-colors">Client</button>
                                        </div>
                                        <button type="button" onclick="startAutoSimulate(<?php echo $order['id']; ?>, <?php echo (float)($order['restaurant_lat']?:27.7215); ?>, <?php echo (float)($order['restaurant_lng']?:85.3210); ?>, <?php echo (float)($order['delivery_lat']?:27.7172); ?>, <?php echo (float)($order['delivery_lng']?:85.3240); ?>, this)" class="w-full py-2 mt-2 bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/30 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-600 hover:text-white shadow-sm transition-all text-center flex justify-center items-center gap-2 transition-colors">
                                            <i data-lucide="play" class="w-3 h-3"></i> Auto Drive Route
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <?php include 'includes/bottom_nav.php'; ?>

    <script>
        lucide.createIcons();
        function setGPS(btn, lat, lng) {
            const form = btn.closest('form');
            form.querySelector('input[name="lat"]').value = lat;
            form.querySelector('input[name="lng"]').value = lng;
        }

        async function startAutoSimulate(orderId, startLat, startLng, endLat, endLng, btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '<i data-lucide="loader" class="w-3 h-3 animate-spin"></i> Driving...';
            lucide.createIcons();

            try {
                // Fetch full route path from our API
                const url = `../api/route.php?order_id=${orderId}&start_lat=${startLat}&start_lng=${startLng}&end_lat=${endLat}&end_lng=${endLng}`;
                const res = await fetch(url);
                const data = await res.json();

                if (data.success && data.geometry) {
                    const coords = data.geometry.coordinates; // array of [lng, lat]
                    let step = 0;

                    // Form reference to update UI fields visually while sending
                    const form = btnElement.closest('form');
                    const latInput = form.querySelector('input[name="lat"]');
                    const lngInput = form.querySelector('input[name="lng"]');

                    const interval = setInterval(async () => {
                        if (step >= coords.length) {
                            clearInterval(interval);
                            btnElement.innerHTML = '<i data-lucide="check" class="w-3 h-3"></i> Arrived';
                            lucide.createIcons();
                            return;
                        }

                        const currentLng = coords[step][0];
                        const currentLat = coords[step][1];

                        // Update Visuals
                        latInput.value = currentLat;
                        lngInput.value = currentLng;

                        // Push to database endpoint like real rider would
                        const formData = new FormData();
                        formData.append('order_id', orderId);
                        formData.append('lat', currentLat);
                        formData.append('lng', currentLng);

                        // Notice we post to our driver_location endpoint, admin context might restrict auth if checking strictly for 'delivery' role.
                        // Here, since the simulator used to post manually to rider_simulator.php, we will post the standard update form dynamically
                        // We will post back to rider_simulator.php's own logic 'update_location' using fetch because update_driver_location requires 'delivery' role.
                        
                        const actionFormData = new FormData();
                        actionFormData.append('update_location', '1');
                        actionFormData.append('order_id', orderId);
                        actionFormData.append('lat', currentLat);
                        actionFormData.append('lng', currentLng);

                        await fetch('rider_simulator.php', {
                            method: 'POST',
                            body: actionFormData
                        });

                        step += Math.max(1, Math.floor(coords.length / 20)); // Jump a few points to make it 20-ish steps
                    }, 5000); // 5 seconds per step as per polling requirement
                } else {
                    alert("Failed to find route geometry for simulation");
                    btnElement.disabled = false;
                    btnElement.innerHTML = '<i data-lucide="play" class="w-3 h-3"></i> Auto Drive Route';
                    lucide.createIcons();
                }
            } catch (e) {
                console.error(e);
                alert("Sim fail: " + e.message);
                btnElement.disabled = false;
                btnElement.innerHTML = '<i data-lucide="play" class="w-3 h-3"></i> Auto Drive Route';
                lucide.createIcons();
            }
        }
    </script>
</body>
</html>


