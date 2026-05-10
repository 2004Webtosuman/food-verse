<?php
// delivery/profile.php — Unified FoodVerse Rider Profile & Verification (Admin Harmony)
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_delivery()) {
    $_SESSION['flash_message'] = "Access denied. Delivery role required.";
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Rider';

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Verification Center - FoodVerse Rider</title>
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
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>Dashboard</span>
                </a>
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="package" class="w-5 h-5"></i><span>My Deliveries</span>
                </a>
                <a href="earnings.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="wallet" class="w-5 h-5"></i><span>Earnings</span>
                </a>
                <a href="reviews.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="star" class="w-5 h-5"></i><span>Reviews</span>
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 bg-primary/5 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
                    <i data-lucide="shield-check" class="w-5 h-5"></i><span>Verification</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
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

            <main class="flex-1 overflow-y-auto p-4 md:p-8 w-full bg-app-bg pb-28 md:pb-8">
                <?php display_flash_message(); ?>

                <div class="mb-10">
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight">Verification Center</h2>
                    <p class="text-sm text-gray-400 mt-0.5 font-medium italic">Legal standards documentation and fleet identification.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                    
                    <!-- Profile Card (Circular Logic) -->
                    <div class="lg:col-span-5 space-y-8">
                        <div class="bg-white p-12 rounded-[3.5rem] border border-card-border shadow-xl shadow-gray-200/40 relative overflow-hidden group">
                            <div class="absolute -right-20 -top-20 w-80 h-80 bg-primary/5 rounded-full blur-[100px] group-hover:scale-110 transition-all duration-1000"></div>
                            
                            <div class="flex flex-col items-center text-center relative z-10">
                                <div class="w-40 h-40 rounded-full bg-gray-50 border-8 border-app-bg shadow-2xl overflow-hidden mb-8 transition-transform hover:rotate-2 relative group-avatar">
                                    <?php if (!empty($user['profile_pic'])): ?>
                                        <img src="../<?php echo $user['profile_pic']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($user_name); ?>" class="w-full h-full object-cover">
                                    <?php endif; ?>
                                    
                                    <!-- Hover Upload Trigger -->
                                    <label for="pfp-upload" class="absolute inset-0 bg-black/40 opacity-0 group-avatar:hover:opacity-100 flex items-center justify-center cursor-pointer transition-all">
                                        <i data-lucide="camera" class="text-white w-8 h-8"></i>
                                    </label>
                                </div>
                                <h3 class="text-4xl font-black text-gray-900 tracking-tighter uppercase italic leading-none mb-3"><?php echo htmlspecialchars($user_name); ?></h3>
                                
                                <?php 
                                    $status_map = [
                                        'unverified' => ['bg-gray-100 text-gray-400', 'Identity Unverified'],
                                        'pending'    => ['bg-orange-50 text-orange-600', 'Awaiting Review'],
                                        'verified'   => ['bg-green-50 text-[#006F5D]', 'Verified Personnel'],
                                        'rejected'   => ['bg-red-50 text-red-600', 'Auth Rejected'],
                                    ];
                                    $s_ctx = $status_map[$user['verification_status'] ?? 'unverified'];
                                ?>
                                <div class="flex items-center gap-2 px-6 py-2 <?php echo $s_ctx[0]; ?> text-[10px] font-black uppercase tracking-widest rounded-full border border-current/10">
                                    <span class="w-2 h-2 rounded-full <?php echo ($user['verification_status'] === 'verified') ? 'bg-green-500 animate-pulse' : 'bg-current'; ?>"></span>
                                    <?php echo $s_ctx[1]; ?>
                                </div>
                            </div>

                            <div class="mt-12 pt-8 border-t border-gray-50 grid grid-cols-2 gap-4 relative z-10">
                                <div class="text-center">
                                    <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-1">Fleet ID</p>
                                    <p class="font-black text-gray-900 text-lg">#FV-<?php echo $user['id']; ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-1">Status</p>
                                    <p class="font-black text-primary text-lg uppercase"><?php echo $user['status']; ?></p>
                                </div>
                            </div>
                        </div>

                        <a href="../logout.php" class="w-full py-6 bg-red-50 text-red-500 border border-red-100 rounded-[2.5rem] font-black uppercase tracking-[0.2em] text-[11px] flex items-center justify-center gap-3 hover:bg-red-500 hover:text-white transition-all active:scale-95 shadow-sm shadow-red-200">
                            <i data-lucide="log-out" class="w-5 h-5"></i> Terminate Session
                        </a>
                    </div>

                    <!-- Submission Flow -->
                    <div class="lg:col-span-7">
                        <form action="actions/update_profile_docs.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                            <input type="file" id="pfp-upload" name="profile_pic" class="hidden" onchange="this.form.submit()">
                            
                            <div class="flex justify-between items-center px-2">
                                 <h3 class="text-xl font-black text-gray-900 tracking-tight uppercase italic flex items-center gap-3">
                                    <i data-lucide="shield-check" class="w-6 h-6 text-primary"></i> Documentation Portal
                                 </h3>
                                 <span class="text-[10px] font-black text-primary uppercase italic">Action Required</span>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <!-- License -->
                                <div class="bg-white p-6 rounded-[2.5rem] border border-card-border shadow-sm flex items-center justify-between group hover:border-primary/20 transition-all">
                                    <div class="flex items-center gap-5">
                                        <div class="w-14 h-14 bg-gray-50 text-gray-400 rounded-2xl flex items-center justify-center border border-card-border group-hover:bg-primary group-hover:text-white transition-all">
                                            <i data-lucide="<?php echo !empty($user['license_doc']) ? 'check-circle' : 'file-text'; ?>" class="w-7 h-7"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black text-gray-900 uppercase">Driving License</h4>
                                            <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mt-0.5">
                                                Status: <?php echo !empty($user['license_doc']) ? '<span class="text-green-500">Transmitted</span>' : 'Awaiting Data'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <label class="px-5 py-2.5 bg-app-bg text-gray-400 text-[9px] font-black uppercase tracking-widest rounded-xl hover:bg-primary hover:text-white cursor-pointer transition-all">
                                        Upload <input type="file" name="license_doc" class="hidden">
                                    </label>
                                </div>

                                <!-- Insurance -->
                                <div class="bg-white p-6 rounded-[2.5rem] border border-card-border shadow-sm flex items-center justify-between group hover:border-primary/20 transition-all">
                                    <div class="flex items-center gap-5">
                                        <div class="w-14 h-14 bg-gray-50 text-gray-400 rounded-2xl flex items-center justify-center border border-card-border group-hover:bg-primary group-hover:text-white transition-all">
                                            <i data-lucide="<?php echo !empty($user['insurance_doc']) ? 'check-circle' : 'file-shield'; ?>" class="w-7 h-7"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black text-gray-900 uppercase">Vehicle Insurance</h4>
                                            <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mt-0.5">
                                                Status: <?php echo !empty($user['insurance_doc']) ? '<span class="text-green-500">Transmitted</span>' : 'Awaiting Data'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <label class="px-5 py-2.5 bg-app-bg text-gray-400 text-[9px] font-black uppercase tracking-widest rounded-xl hover:bg-primary hover:text-white cursor-pointer transition-all">
                                        Upload <input type="file" name="insurance_doc" class="hidden">
                                    </label>
                                </div>

                                <!-- ID Card -->
                                <div class="bg-white p-6 rounded-[2.5rem] border border-card-border shadow-sm flex items-center justify-between group hover:border-primary/20 transition-all">
                                    <div class="flex items-center gap-5">
                                        <div class="w-14 h-14 bg-gray-50 text-gray-400 rounded-2xl flex items-center justify-center border border-card-border group-hover:bg-primary group-hover:text-white transition-all">
                                            <i data-lucide="<?php echo !empty($user['id_card_doc']) ? 'check-circle' : 'fingerprint'; ?>" class="w-7 h-7"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black text-gray-900 uppercase">National ID Card</h4>
                                            <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mt-0.5">
                                                Status: <?php echo !empty($user['id_card_doc']) ? '<span class="text-green-500">Transmitted</span>' : 'Awaiting Data'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <label class="px-5 py-2.5 bg-app-bg text-gray-400 text-[9px] font-black uppercase tracking-widest rounded-xl hover:bg-primary hover:text-white cursor-pointer transition-all">
                                        Upload <input type="file" name="id_card_doc" class="hidden">
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="w-full py-6 bg-primary text-white rounded-[2.5rem] font-black uppercase tracking-[0.2em] text-[12px] shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-3">
                                <i data-lucide="upload-cloud" class="w-5 h-5"></i> Submit For Verification
                            </button>
                        </form>
                    </div>
                </div>

            </main>

            </main>
        </div>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
