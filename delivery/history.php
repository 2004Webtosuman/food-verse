<?php
// delivery/history.php — Unified FoodVerse Rider Deliveries History (Admin Harmony)
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_delivery()) {
    $_SESSION['flash_message'] = "Access denied. Delivery role required.";
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Legit Mission Logs: Fetching real database records
$stmt = $pdo->prepare("SELECT o.*, u.full_name as client 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id
                       WHERE o.delivery_user_id = ? 
                       ORDER BY o.created_at DESC");
$stmt->execute([$user_id]);
$legit_missions = $stmt->fetchAll();

// Statistics (Real database-driven)
$total_assigned = count($legit_missions);
$delivered_count = 0;
foreach ($legit_missions as $m) if ($m['status'] == 'delivered') $delivered_count++;

$success_rate = $total_assigned > 0 ? ($delivered_count / $total_assigned) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Mission Logs - FoodVerse Rider</title>
</head>
<body class="bg-app-bg min-h-screen">

    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-card-border hidden md:flex flex-col h-full z-10 flex-shrink-0">
            <div class="p-6 border-b border-card-border flex items-center h-[88px] gap-2">
                <h1 class="text-xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                <span class="bg-primary/10 text-primary text-[8px] font-bold px-2 py-0.5 rounded-full uppercase ml-1">Rider Hub</span>
            </div>
            <nav class="flex-1 py-6 space-y-2 px-4 overflow-y-auto w-full leading-none">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>Dashboard</span>
                </a>
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 bg-primary/5 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
                    <i data-lucide="package" class="w-5 h-5"></i><span>My Deliveries</span>
                </a>
                <a href="earnings.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="wallet" class="w-5 h-5"></i><span>Earnings</span>
                </a>
                <a href="reviews.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="star" class="w-5 h-5"></i><span>Reviews</span>
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="shield-check" class="w-5 h-5"></i><span>Verification</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header class="bg-white border-b border-card-border h-[72px] md:h-[88px] flex items-center justify-between md:justify-end px-6 md:px-8 z-10 flex-shrink-0">
                <div class="md:hidden flex items-center gap-2">
                    <h1 class="text-2xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="settings.php" class="text-gray-500 hover:text-primary transition-colors">
                        <i data-lucide="settings" class="w-6 h-6"></i>
                    </a>
                    <a href="../logout.php" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium transition-all">
                        <i data-lucide="power" class="w-5 h-5"></i><span class="hidden md:inline">Logout</span>
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-8 w-full bg-app-bg pb-28 md:pb-8">
                
                <div class="mb-12">
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight uppercase italic underline decoration-primary/20">Operations Vault</h2>
                    <p class="text-sm text-gray-400 mt-0.5 font-medium italic">Verified historical log of all missions assigned to your logistics profile.</p>
                </div>

                <!-- Stats Summary -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                    <div class="bg-white p-6 rounded-[2.5rem] border border-card-border flex flex-col justify-between h-[150px] shadow-sm relative group overflow-hidden transition-all hover:border-primary/20">
                        <div class="flex items-center gap-3 relative z-10">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-500"><i data-lucide="package" class="w-5 h-5"></i></div>
                            <span class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Lifetime Shipments</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900 relative z-10"><?php echo number_format($delivered_count); ?></p>
                        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-blue-500/5 rounded-full group-hover:scale-150 transition-all duration-700"></div>
                    </div>
                    <div class="bg-white p-6 rounded-[2.5rem] border border-card-border flex flex-col justify-between h-[150px] shadow-sm relative group overflow-hidden transition-all hover:border-primary/20">
                        <div class="flex items-center gap-3 relative z-10">
                            <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center text-green-500"><i data-lucide="crosshair" class="w-5 h-5"></i></div>
                            <span class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Efficiency Rating</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900 relative z-10"><?php echo number_format($success_rate, 1); ?>%</p>
                        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-green-500/5 rounded-full group-hover:scale-150 transition-all duration-700"></div>
                    </div>
                </div>

                <!-- Mission Table (Admin Symmetry) -->
                <div class="bg-white rounded-[3rem] shadow-xl shadow-gray-200/40 border border-card-border p-8 overflow-hidden">
                    <div class="flex justify-between items-center mb-10 overflow-x-auto no-scrollbar gap-4">
                        <h3 class="text-xl font-black text-gray-900 uppercase italic leading-none">Mission Archive</h3>
                        <div class="flex items-center gap-2 px-6 py-3 bg-gray-50 border border-card-border rounded-xl text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            <i data-lucide="filter" class="w-4 h-4"></i> Data Verified
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left whitespace-nowrap">
                            <thead>
                                <tr class="border-b border-card-border">
                                    <th class="pb-6 text-[10px] uppercase font-black text-gray-400 tracking-[0.2em]">MISSION</th>
                                    <th class="pb-6 text-[10px] uppercase font-black text-gray-400 tracking-[0.2em]">CLIENT</th>
                                    <th class="pb-6 text-[10px] uppercase font-black text-gray-400 tracking-[0.2em]">EXECUTION DATE</th>
                                    <th class="pb-6 text-[10px] uppercase font-black text-gray-400 tracking-[0.2em] text-center">STATUS</th>
                                    <th class="pb-6 text-[10px] uppercase font-black text-gray-400 tracking-[0.2em] text-right">YIELD</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-card-border">
                                <?php if (empty($legit_missions)): ?>
                                    <tr>
                                        <td colspan="5" class="py-20 text-center text-gray-400 font-medium italic uppercase tracking-widest text-[9px]">No historical missions identified in local mainframe.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($legit_missions as $m): 
                                        $status_class = match($m['status']) {
                                            'delivered'       => 'bg-green-50 text-green-600 border-green-200',
                                            'cancelled'       => 'bg-red-50 text-red-500 border-red-200',
                                            'out_for_delivery'=> 'bg-blue-50 text-blue-500 border-blue-200',
                                            default           => 'bg-orange-50 text-orange-500 border-orange-200',
                                        };
                                        $earning = $m['status'] == 'delivered' ? 50.00 : 0.00;
                                    ?>
                                        <tr class="group hover:bg-gray-50/50 transition-all duration-300">
                                            <td class="py-6">
                                                <a href="view_order.php?id=<?php echo $m['id']; ?>" class="font-black text-primary text-sm uppercase italic hover:underline">#AT-<?php echo $m['id']; ?></a>
                                            </td>
                                            <td class="py-6"><span class="font-black text-gray-900 text-xs uppercase tracking-tight"><?php echo htmlspecialchars($m['client']); ?></span></td>
                                            <td class="py-6">
                                                <div class="font-bold text-[11px] text-gray-900"><?php echo date('M d, Y', strtotime($m['created_at'])); ?></div>
                                                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1"><?php echo date('H:i', strtotime($m['created_at'])); ?> HRS</div>
                                            </td>
                                            <td class="py-6 text-center">
                                                <span class="px-3 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest border <?php echo $status_class; ?>">
                                                    <?php echo str_replace('_', ' ', $m['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-6 text-right">
                                                <p class="font-black text-gray-900 text-sm">Rs. <?php echo number_format($earning, 2); ?></p>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
