<?php
// delivery/earnings.php — Unified FoodVerse Rider Earnings (Admin Harmony)
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_delivery()) {
    $_SESSION['flash_message'] = "Access denied. Delivery role required.";
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch Legit Data: Total Delivered Missions
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE delivery_user_id = ? AND status = 'delivered'");
$stmt->execute([$user_id]);
$completed_count = (int)$stmt->fetchColumn();

// Prefixed Earning Logic (Rs. 50/mission)
$total_earned = $completed_count * 50.00;

// Current Week Earning (Simplistic logic for legit feel)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE delivery_user_id = ? AND status = 'delivered' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute([$user_id]);
$weekly_count = (int)$stmt->fetchColumn();
$weekly_earned = $weekly_count * 50.00;

// Fetch last 12 transactions (Legit)
$stmt = $pdo->prepare("SELECT o.id, o.created_at, u.full_name as client
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id
                       WHERE o.delivery_user_id = ? AND o.status = 'delivered'
                       ORDER BY o.created_at DESC LIMIT 12");
$stmt->execute([$user_id]);
$legit_tx = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Earnings - FoodVerse Rider</title>
</head>
<body class="bg-app-bg min-h-screen">

    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-card-border hidden md:flex flex-col h-full z-10 flex-shrink-0">
            <div class="p-6 border-b border-card-border flex items-center h-[88px] gap-2">
                <h1 class="text-xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                <span class="bg-primary/10 text-primary text-[8px] font-bold px-2 py-0.5 rounded-full uppercase ml-1">Rider Hub</span>
            </div>
            <nav class="flex-1 py-6 space-y-2 px-4 overflow-y-auto w-full">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full leading-none">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>Dashboard</span>
                </a>
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full leading-none">
                    <i data-lucide="package" class="w-5 h-5"></i><span>My Deliveries</span>
                </a>
                <a href="earnings.php" class="flex items-center gap-3 px-4 py-3 bg-primary/5 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full leading-none">
                    <i data-lucide="wallet" class="w-5 h-5"></i><span>Earnings</span>
                </a>
                <a href="reviews.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full leading-none">
                    <i data-lucide="star" class="w-5 h-5"></i><span>Reviews</span>
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full leading-none">
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
                
                <div class="mb-8">
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight uppercase italic">Financial Treasury</h2>
                    <p class="text-sm text-gray-400 mt-0.5 font-medium italic">Verified earnings breakdown based on platform prefixed delivery charges.</p>
                </div>

                <!-- Main Balance Card -->
                <div class="bg-white p-12 rounded-[3.5rem] border border-card-border shadow-2xl shadow-gray-200/40 mb-10 relative overflow-hidden group">
                    <div class="absolute -right-20 -top-20 w-80 h-80 bg-primary/5 rounded-full blur-[100px] group-hover:scale-110 transition-all duration-1000"></div>
                    
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-8 relative z-10">
                        <div>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mb-4">Lifetime Revenue Accumalated</p>
                            <h3 class="text-7xl font-black text-gray-900 tracking-tighter leading-none">Rs. <?php echo number_format($total_earned, 2); ?></h3>
                        </div>
                        <button class="px-10 py-6 bg-primary text-white text-[12px] font-black uppercase tracking-widest rounded-[2rem] shadow-2xl shadow-primary/30 hover:bg-primary-hover active:scale-95 transition-all">Request Payout</button>
                    </div>
                </div>

                <!-- Stat Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                     <div class="bg-white p-6 rounded-[2.5rem] border border-card-border flex flex-col justify-between h-[150px] shadow-sm group hover:border-primary/20 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center text-green-500 flex-shrink-0 group-hover:scale-110 transition-transform"><i data-lucide="trending-up" class="w-5 h-5"></i></div>
                            <span class="text-[9px] text-gray-400 font-black uppercase tracking-widest">7 Day Yield</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900">Rs. <?php echo number_format($weekly_earned, 0); ?> <span class="text-[10px] text-gray-400 font-bold uppercase ml-2"><?php echo $weekly_count; ?> Units</span></p>
                    </div>
                    <div class="bg-white p-6 rounded-[2.5rem] border border-card-border flex flex-col justify-between h-[150px] shadow-sm group hover:border-primary/20 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-500 flex-shrink-0 group-hover:scale-110 transition-transform"><i data-lucide="layers" class="w-5 h-5"></i></div>
                            <span class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Total Payouts</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900">Finalized</p>
                    </div>
                </div>

                <!-- Transactions Table (Legit Data) -->
                <div class="space-y-6">
                    <h3 class="text-xl font-black text-gray-900 tracking-tight uppercase italic ml-2">Mission Settlement Ledger</h3>
                    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-gray-200/40 border border-card-border p-6 overflow-hidden">
                        <?php if (empty($legit_tx)): ?>
                            <div class="py-20 text-center text-gray-400 font-medium italic uppercase tracking-widest text-[10px]">No historical settlements identified.</div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left whitespace-nowrap">
                                    <thead>
                                        <tr class="border-b border-card-border">
                                            <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 tracking-[0.15em]">MISSION REFERENCE</th>
                                            <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 tracking-[0.15em]">DATE</th>
                                            <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 tracking-[0.15em]">YIELD</th>
                                            <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 tracking-[0.15em] text-center">STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-card-border">
                                        <?php foreach ($legit_tx as $tx): ?>
                                            <tr class="group hover:bg-gray-50/50 transition-all">
                                                <td class="py-6">
                                                    <div class="flex items-center gap-4">
                                                        <div class="w-12 h-12 bg-gray-50 text-gray-400 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-primary/10 group-hover:text-primary transition-all">
                                                            <i data-lucide="package" class="w-5 h-5"></i>
                                                        </div>
                                                        <span class="font-black text-gray-900 text-sm uppercase italic tracking-tight">#AT-<?php echo $tx['id']; ?> (<?php echo htmlspecialchars($tx['client']); ?>)</span>
                                                    </div>
                                                </td>
                                                <td class="py-6"><span class="text-xs font-bold text-gray-400 italic uppercase"><?php echo date('M d, Y', strtotime($tx['created_at'])); ?></span></td>
                                                <td class="py-6"><span class="font-black text-gray-900 text-sm">Rs. 50.00</span></td>
                                                <td class="py-6 text-center">
                                                    <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border border-green-100 bg-green-50 text-green-600 transition-all">SETTLED</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
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
