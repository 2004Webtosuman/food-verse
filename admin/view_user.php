<?php
// admin/view_user.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_admin()) redirect('../login.php');

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user = null;
$stats = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        // Fetch some basic stats for the user
        $order_stmt = $pdo->prepare("SELECT COUNT(*) as total_orders, SUM(total_price) as total_spent FROM orders WHERE user_id = ?");
        $order_stmt->execute([$user_id]);
        $stats = $order_stmt->fetch();
    }
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Error: " . $e->getMessage();
}

if (!$user) {
    $_SESSION['flash_message'] = "User not found.";
    redirect('users.php');
}
?>
<head>
    <?php include 'includes/head.php'; ?>
    <title>User Profile - <?php echo sanitize($user['full_name']); ?> - FoodVerse</title>
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
                <a href="users.php" class="flex items-center gap-3 px-4 py-3 bg-primary/5 dark:bg-primary/10 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
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
                <?php display_flash_message(); ?>

                <!-- Compact Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div>
                        <a href="users.php" class="inline-flex items-center gap-1.5 text-gray-400 hover:text-primary transition-all font-bold text-[10px] uppercase tracking-widest mb-1">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Users
                        </a>
                        <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">User Profile</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="document.getElementById('emailModal').classList.remove('hidden')" class="w-full sm:w-auto bg-primary/10 text-primary border border-primary/20 px-4 py-2.5 rounded-xl font-bold shadow-sm hover:bg-primary hover:text-white transition-all flex items-center justify-center gap-2 text-[10px] uppercase tracking-wider">
                            <i data-lucide="mail" class="w-4 h-4"></i> Message
                        </button>
                        <form action="actions/update_user_status.php" method="POST" class="w-full sm:w-auto">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <?php if ($user['status'] === 'active'): ?>
                                <input type="hidden" name="status" value="suspended">
                                <button type="submit" class="w-full sm:w-auto bg-red-50 text-red-600 border border-red-200 px-4 py-2.5 rounded-xl font-bold shadow-sm hover:bg-red-500 hover:text-white transition-all flex items-center justify-center gap-2 text-[10px] uppercase tracking-wider">
                                    <i data-lucide="user-x" class="w-4 h-4"></i> Suspend
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="w-full sm:w-auto bg-green-50 text-green-600 border border-green-200 px-4 py-2.5 rounded-xl font-bold shadow-sm hover:bg-green-500 hover:text-white transition-all flex items-center justify-center gap-2 text-[10px] uppercase tracking-wider">
                                    <i data-lucide="user-check" class="w-4 h-4"></i> Activate
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Email Modal -->
                <div id="emailModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white dark:bg-dark-card rounded-3xl w-full max-w-lg shadow-[0_20px_60px_rgba(0,0,0,0.1)] border border-card-border dark:border-dark-border overflow-hidden transition-colors">
                        <div class="p-6 border-b border-card-border dark:border-dark-border flex justify-between items-center bg-gray-50/50 dark:bg-dark-bg/50">
                            <h3 class="text-xl font-black text-gray-900 dark:text-white tracking-tight">Compose Email to <?php echo sanitize($user['full_name']); ?></h3>
                            <button onclick="document.getElementById('emailModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 bg-white dark:bg-dark-bg rounded-xl shadow-sm border border-card-border dark:border-dark-border transition-all">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <form action="actions/send_user_email.php" method="POST" enctype="multipart/form-data" class="p-6">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <div class="mb-5">
                                <label class="block text-[10px] font-black tracking-widest uppercase text-gray-400 mb-2">To</label>
                                <input type="text" value="<?php echo sanitize($user['email']); ?>" disabled class="w-full bg-gray-50 border border-card-border rounded-xl px-4 py-3 text-sm text-gray-500 font-medium">
                            </div>
                            <div class="mb-5">
                                <label class="block text-[10px] font-black tracking-widest uppercase text-gray-400 mb-2">Subject</label>
                                <input type="text" name="subject" required placeholder="Important Account Notice" class="w-full bg-white dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white font-bold focus:outline-none focus:border-primary/50 focus:ring-4 focus:ring-primary/10 transition-all">
                            </div>
                            <div class="mb-5">
                                <label class="block text-[10px] font-black tracking-widest uppercase text-gray-400 mb-2">Message</label>
                                <textarea name="message" required rows="5" placeholder="Write your message here..." class="w-full bg-white dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:border-primary/50 focus:ring-4 focus:ring-primary/10 transition-all resize-none"></textarea>
                            </div>
                            <div class="mb-6">
                                <label class="block text-[10px] font-black tracking-widest uppercase text-gray-400 mb-2">Attachments (Images, Videos, PDFs)</label>
                                <input type="file" name="attachments[]" multiple class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border file:border-card-border file:bg-gray-50 file:text-[10px] file:font-black file:uppercase file:tracking-widest file:text-gray-600 hover:file:bg-gray-100 transition-all cursor-pointer">
                            </div>
                            <div class="flex justify-end pt-2">
                                <button type="submit" class="bg-primary text-white font-bold text-sm px-8 py-3 rounded-xl shadow-lg shadow-primary/20 hover:bg-primary-hover active:scale-95 transition-all flex items-center gap-2">
                                    <i data-lucide="send" class="w-4 h-4"></i> Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-1 space-y-8">
                        <div class="bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-8 text-center transition-colors">
                            <div class="w-32 h-32 bg-section-bg dark:bg-dark-bg border border-primary/20 rounded-full mx-auto mb-6 flex items-center justify-center text-primary overflow-hidden transition-colors">
                                <?php if (!empty($user['profile_pic']) && file_exists('../' . $user['profile_pic'])): ?>
                                    <img src="../<?php echo $user['profile_pic']; ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i data-lucide="user" class="w-12 h-12"></i>
                                <?php endif; ?>
                            </div>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-1 transition-colors"><?php echo sanitize($user['full_name']); ?></h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-6 font-medium text-sm transition-colors"><?php echo sanitize($user['email']); ?></p>
                            <span class="px-6 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest border <?php echo $user['status'] === 'active' ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200'; ?>">
                                <?php echo $user['status']; ?>
                            </span>
                        </div>

                        <div class="bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-8 transition-colors">
                            <h4 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 border-b border-card-border dark:border-dark-border pb-4 mb-6 uppercase tracking-widest transition-colors">Account Details</h4>
                            <div class="space-y-6">
                                <div>
                                    <p class="text-gray-400 dark:text-gray-500 text-[10px] uppercase font-bold tracking-widest mb-1 transition-colors">Joined Date</p>
                                    <p class="font-bold text-gray-900 dark:text-white text-sm transition-colors"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest mb-1">Role</p>
                                    <?php if ($user['role'] === 'delivery'): ?>
                                        <p class="font-bold text-purple-600 text-sm uppercase tracking-wide flex items-center gap-1">
                                            <i data-lucide="bike" class="w-4 h-4"></i> Rider
                                        </p>
                                    <?php else: ?>
                                        <p class="font-bold text-blue-600 text-sm uppercase tracking-wide flex items-center gap-1">
                                            <i data-lucide="shopping-bag" class="w-4 h-4"></i> Customer
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-8">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-8 flex flex-col justify-between transition-colors">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="p-4 bg-blue-50 dark:bg-blue-500/10 text-blue-500 rounded-2xl transition-colors">
                                        <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                                    </div>
                                    <h4 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest transition-colors">Total Orders</h4>
                                </div>
                                <p class="text-4xl font-black text-gray-900 dark:text-white transition-colors"><?php echo $stats['total_orders'] ?? 0; ?></p>
                            </div>
                            <div class="bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-8 flex flex-col justify-between transition-colors">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="p-4 bg-green-50 dark:bg-green-500/10 text-green-500 rounded-2xl transition-colors">
                                        <i data-lucide="dollar-sign" class="w-6 h-6"></i>
                                    </div>
                                    <h4 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest transition-colors">Lifetime Spends</h4>
                                </div>
                                <p class="text-3xl font-black text-gray-900 dark:text-white truncate transition-colors">Rs. <?php echo number_format($stats['total_spent'] ?? 0, 0); ?></p>
                            </div>
                        </div>

                        <?php if ($user['role'] === 'delivery'): ?>
                        <!-- Rider Verification Center (Admin HUD) -->
                        <div class="bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-8 mb-8 transition-colors">
                            <div class="flex items-center justify-between mb-8 pb-4 border-b border-card-border dark:border-dark-border transition-colors">
                                <h4 class="text-xl font-black text-gray-900 dark:text-white uppercase italic transition-colors">Rider Management Hub</h4>
                                <div class="flex items-center gap-2">
                                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Global Status:</span>
                                    <span class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border 
                                        <?php echo match($user['verification_status'] ?? 'unverified') {
                                            'verified' => 'bg-green-50 text-green-600 border-green-200',
                                            'pending'  => 'bg-orange-50 text-orange-600 border-orange-200',
                                            'rejected' => 'bg-red-50 text-red-600 border-red-200',
                                            default    => 'bg-gray-100 text-gray-400 border-gray-200',
                                        }; ?>">
                                        <?php echo $user['verification_status'] ?? 'Unverified'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                                <!-- License Card -->
                                <div class="bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-3xl p-6 hover:bg-white dark:hover:bg-dark-card hover:border-primary/20 transition-all group transition-colors">
                                    <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4 transition-colors">Driving License</p>
                                    <?php if (!empty($user['license_doc'])): ?>
                                        <a href="../<?php echo $user['license_doc']; ?>" target="_blank" class="w-full h-32 bg-white dark:bg-dark-card rounded-2xl border border-card-border dark:border-dark-border flex items-center justify-center text-primary hover:text-white hover:bg-primary transition-all shadow-sm transition-colors">
                                            <i data-lucide="eye" class="w-8 h-8"></i>
                                        </a>
                                        <p class="text-[9px] text-green-500 font-black uppercase mt-3 italic flex items-center gap-1 transition-colors"><i data-lucide="check" class="w-3 h-3"></i> Transmitted</p>
                                    <?php else: ?>
                                        <div class="w-full h-32 bg-gray-100 dark:bg-dark-bg rounded-2xl border border-dashed border-gray-200 dark:border-dark-border flex items-center justify-center text-gray-300 dark:text-gray-700 transition-colors">
                                            <i data-lucide="file-off" class="w-8 h-8"></i>
                                        </div>
                                        <p class="text-[9px] text-gray-300 dark:text-gray-700 font-black uppercase mt-3 italic transition-colors">Not Provided</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Insurance Card -->
                                <div class="bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-3xl p-6 hover:bg-white dark:hover:bg-dark-card hover:border-primary/20 transition-all group transition-colors">
                                    <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4 transition-colors">Vehicle Insurance</p>
                                    <?php if (!empty($user['insurance_doc'])): ?>
                                        <a href="../<?php echo $user['insurance_doc']; ?>" target="_blank" class="w-full h-32 bg-white dark:bg-dark-card rounded-2xl border border-card-border dark:border-dark-border flex items-center justify-center text-primary hover:text-white hover:bg-primary transition-all shadow-sm transition-colors">
                                            <i data-lucide="eye" class="w-8 h-8"></i>
                                        </a>
                                        <p class="text-[9px] text-green-500 font-black uppercase mt-3 italic flex items-center gap-1 transition-colors"><i data-lucide="check" class="w-3 h-3"></i> Transmitted</p>
                                    <?php else: ?>
                                        <div class="w-full h-32 bg-gray-100 dark:bg-dark-bg rounded-2xl border border-dashed border-gray-200 dark:border-dark-border flex items-center justify-center text-gray-300 dark:text-gray-700 transition-colors">
                                            <i data-lucide="file-off" class="w-8 h-8"></i>
                                        </div>
                                        <p class="text-[9px] text-gray-300 dark:text-gray-700 font-black uppercase mt-3 italic transition-colors">Not Provided</p>
                                    <?php endif; ?>
                                </div>

                                <!-- ID Card Card -->
                                <div class="bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-3xl p-6 hover:bg-white dark:hover:bg-dark-card hover:border-primary/20 transition-all group transition-colors">
                                    <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4 transition-colors">National ID</p>
                                    <?php if (!empty($user['id_card_doc'])): ?>
                                        <a href="../<?php echo $user['id_card_doc']; ?>" target="_blank" class="w-full h-32 bg-white dark:bg-dark-card rounded-2xl border border-card-border dark:border-dark-border flex items-center justify-center text-primary hover:text-white hover:bg-primary transition-all shadow-sm transition-colors">
                                            <i data-lucide="eye" class="w-8 h-8"></i>
                                        </a>
                                        <p class="text-[9px] text-green-500 font-black uppercase mt-3 italic flex items-center gap-1 transition-colors"><i data-lucide="check" class="w-3 h-3"></i> Transmitted</p>
                                    <?php else: ?>
                                        <div class="w-full h-32 bg-gray-100 dark:bg-dark-bg rounded-2xl border border-dashed border-gray-200 dark:border-dark-border flex items-center justify-center text-gray-300 dark:text-gray-700 transition-colors">
                                            <i data-lucide="file-off" class="w-8 h-8"></i>
                                        </div>
                                        <p class="text-[9px] text-gray-300 dark:text-gray-700 font-black uppercase mt-3 italic transition-colors">Not Provided</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Verification Actions -->
                            <div class="flex flex-col sm:flex-row items-center gap-4">
                                <form action="actions/verify_rider.php" method="POST" class="w-full flex-1">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="verified">
                                    <button type="submit" class="w-full bg-green-500 text-white font-black uppercase tracking-widest text-[11px] py-5 rounded-2xl shadow-xl shadow-green-500/20 hover:bg-green-600 active:scale-95 transition-all flex items-center justify-center gap-3">
                                        <i data-lucide="shield-check" class="w-5 h-5"></i> Approve Rider
                                    </button>
                                </form>
                                <form action="actions/verify_rider.php" method="POST" class="w-full flex-1">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="rejected">
                                    <button type="submit" class="w-full bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-500 border border-red-200 dark:border-red-500/30 font-black uppercase tracking-widest text-[11px] py-5 rounded-2xl hover:bg-red-600 dark:hover:bg-red-600 hover:text-white dark:hover:text-white active:scale-95 transition-all flex items-center justify-center gap-3 transition-colors">
                                        <i data-lucide="shield-alert" class="w-5 h-5"></i> Reject Docs
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-8 transition-colors">
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-6 transition-colors">Recent Activity</h4>
                            <div class="flex flex-col items-center justify-center py-10 text-gray-400 dark:text-gray-500 transition-colors">
                                <div class="w-16 h-16 bg-gray-50 dark:bg-dark-bg flex items-center justify-center rounded-2xl mb-4 border border-gray-100 dark:border-dark-border transition-colors">
                                    <i data-lucide="activity" class="w-8 h-8 text-gray-300 dark:text-gray-700 transition-colors"></i>
                                </div>
                                <p class="font-medium text-sm">No recent activity logs found</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script> lucide.createIcons(); </script>
</body>
</html>
