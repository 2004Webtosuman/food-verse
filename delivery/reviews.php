<?php
// delivery/reviews.php — Unified FoodVerse Rider Reviews (Admin Harmony)
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_delivery()) {
    $_SESSION['flash_message'] = "Access denied. Delivery role required.";
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// LEGIT DATA FETCHING: Direct ratings for this rider
$stmt = $pdo->prepare("SELECT rr.*, u.full_name as reviewer_name, u.profile_pic as reviewer_image 
                       FROM rider_ratings rr 
                       JOIN users u ON rr.user_id = u.id 
                       WHERE rr.rider_id = ? 
                       ORDER BY rr.created_at DESC");
$stmt->execute([$user_id]);
$reviews = $stmt->fetchAll();

// Statistics calculation based on direct rider ratings
$total_reviews = count($reviews);
$avg_rating = $total_reviews > 0 ? array_sum(array_column($reviews, 'rating')) / $total_reviews : 0;
$rank = $total_reviews > 10 ? "Elite Pro" : ($total_reviews > 5 ? "Experienced" : ($total_reviews > 0 ? "Qualified" : "Initiate"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Reputation - FoodVerse Rider</title>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 107, 53, 0.2); border-radius: 10px; }
    </style>
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
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="package" class="w-5 h-5"></i><span>My Deliveries</span>
                </a>
                <a href="earnings.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="wallet" class="w-5 h-5"></i><span>Earnings</span>
                </a>
                <a href="reviews.php" class="flex items-center gap-3 px-4 py-3 bg-primary/5 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
                    <i data-lucide="star" class="w-5 h-5"></i><span>Reviews</span>
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="shield-check" class="w-5 h-5"></i><span>Verification</span>
                </a>
            </nav>
        </aside>

        <!-- Main Wrapper -->
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
                
                <div class="mb-10">
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight uppercase italic underline decoration-primary/20">Operational Reputation</h2>
                    <p class="text-sm text-gray-400 mt-0.5 font-medium italic">Direct customer feedback based on your fleet Performance.</p>
                </div>

                <!-- Reputation Snapshot -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                    <div class="bg-white p-8 rounded-[2.5rem] border border-card-border shadow-sm group hover:scale-[1.02] transition-all">
                        <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-4">Service Index</p>
                        <div class="flex items-end gap-3">
                            <h3 class="text-5xl font-black text-gray-900 leading-none"><?php echo number_format($avg_rating, 1); ?></h3>
                            <div class="flex mb-1 text-primary">
                                <?php for($i=0; $i<5; $i++): ?>
                                    <i data-lucide="star" class="w-4 h-4 <?php echo $i < floor($avg_rating) ? 'fill-primary' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-[2.5rem] border border-card-border shadow-sm group hover:scale-[1.02] transition-all">
                        <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-4">Rating Volume</p>
                        <h3 class="text-5xl font-black text-gray-900 leading-none"><?php echo $total_reviews; ?></h3>
                        <p class="text-[10px] text-accent font-black uppercase tracking-widest mt-4 flex items-center gap-2"><i data-lucide="check-circle" class="w-3 h-3"></i> Fleet Verified</p>
                    </div>
                    <div class="bg-white p-8 rounded-[2.5rem] border border-card-border shadow-sm group hover:scale-[1.02] transition-all">
                        <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-4">Expertise Tier</p>
                        <h3 class="text-3xl font-black text-primary leading-none uppercase italic"><?php echo $rank; ?></h3>
                        <p class="text-[9px] text-gray-400 font-bold uppercase mt-6 tracking-widest">Global Logistics Rank</p>
                    </div>
                </div>

                <!-- Feed -->
                <div class="space-y-6">
                    <h3 class="text-xl font-black text-gray-900 uppercase italic ml-2">Customer Feedback Stream</h3>
                    
                    <?php if (empty($reviews)): ?>
                        <div class="bg-white p-20 rounded-[3rem] border border-card-border text-center shadow-sm">
                            <div class="w-20 h-20 bg-gray-50 text-gray-200 rounded-3xl flex items-center justify-center mx-auto mb-6">
                                <i data-lucide="message-square-off" class="w-10 h-10"></i>
                            </div>
                            <h4 class="text-lg font-black text-gray-900 uppercase italic">Silence is Golden</h4>
                            <p class="text-sm text-gray-400 font-medium italic">Complete more successful missions to generate direct feedback.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($reviews as $rev): ?>
                                <div class="bg-white p-8 rounded-[3rem] border border-card-border shadow-sm hover:shadow-xl transition-all relative overflow-hidden group">
                                    <div class="flex justify-between items-start mb-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 bg-gray-50 rounded-full border border-card-border p-1 overflow-hidden">
                                                <?php if (!empty($rev['reviewer_image']) && file_exists('../' . $rev['reviewer_image'])): ?>
                                                    <img src="../<?php echo $rev['reviewer_image']; ?>" class="w-full h-full object-cover rounded-full">
                                                <?php else: ?>
                                                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($rev['reviewer_name']); ?>" class="w-full h-full object-cover rounded-full">
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h4 class="text-[11px] font-black text-gray-900 uppercase tracking-widest leading-none"><?php echo htmlspecialchars($rev['reviewer_name']); ?></h4>
                                                <div class="flex gap-0.5 text-primary mt-1.5">
                                                    <?php for($i=0; $i<5; $i++): ?>
                                                        <i data-lucide="star" class="w-2.5 h-2.5 <?php echo $i < $rev['rating'] ? 'fill-primary' : ''; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="text-[9px] text-gray-300 font-black uppercase tracking-widest">
                                            <?php echo date('M d, Y', strtotime($rev['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="p-6 bg-app-bg rounded-2xl border border-card-border group-hover:bg-white transition-all">
                                        <p class="text-sm text-gray-600 font-medium italic leading-relaxed">"<?php echo htmlspecialchars($rev['review']); ?>"</p>
                                    </div>
                                    <div class="mt-4 flex items-center justify-between">
                                        <span class="text-[9px] text-primary font-black uppercase tracking-widest">Order #FE-<?php echo $rev['order_id']; ?></span>
                                        <div class="flex items-center gap-2 text-green-500">
                                            <i data-lucide="award" class="w-3 h-3"></i>
                                            <span class="text-[8px] font-black uppercase">Verified Service</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
