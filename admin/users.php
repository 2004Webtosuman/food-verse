<?php
// admin/users.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_admin()) redirect('../login.php');

$role_filter = $_GET['role'] ?? 'all';

$query = "SELECT * FROM users WHERE role != 'admin' ";
if ($role_filter === 'user') {
    $query .= "AND role = 'user' ";
} elseif ($role_filter === 'delivery') {
    $query .= "AND role = 'delivery' ";
}
$query .= "ORDER BY created_at DESC";

try {
    $stmt = $pdo->query($query);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}

// Counts
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalRiders = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'delivery'")->fetchColumn();
?>
<head>
    <?php include 'includes/head.php'; ?>
    <title>Manage Users - FoodVerse</title>
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

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto p-4 md:p-8 w-full bg-app-bg dark:bg-dark-bg pb-28 md:pb-8 transition-colors">
                <?php display_flash_message(); ?>

                <!-- Page heading -->
                <div class="mb-5">
                    <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">Users</h2>
                    <p class="text-sm text-gray-400 mt-0.5 font-medium">Manage customers and delivery personnel.</p>
                </div>

                <!-- Role filter pills -->
                <div class="flex flex-wrap gap-2 mb-5">
                    <a href="users.php?role=all" class="px-4 py-2 rounded-full text-xs font-bold transition-all border transition-colors <?php echo $role_filter === 'all' ? 'bg-primary text-white border-primary shadow-lg shadow-primary/20' : 'bg-white dark:bg-dark-card text-gray-500 dark:text-gray-400 border-card-border dark:border-dark-border'; ?>">
                        All Users
                    </a>
                    <a href="users.php?role=user" class="px-4 py-2 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 border transition-colors <?php echo $role_filter === 'user' ? 'bg-primary text-white border-primary shadow-lg shadow-primary/20' : 'bg-white dark:bg-dark-card text-gray-500 dark:text-gray-400 border-card-border dark:border-dark-border'; ?>">
                        <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                        Customers <span class="opacity-70">(<?php echo $totalUsers; ?>)</span>
                    </a>
                    <a href="users.php?role=delivery" class="px-4 py-2 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 border transition-colors <?php echo $role_filter === 'delivery' ? 'bg-primary text-white border-primary shadow-lg shadow-primary/20' : 'bg-white dark:bg-dark-card text-gray-500 dark:text-gray-400 border-card-border dark:border-dark-border'; ?>">
                        <i data-lucide="bike" class="w-3.5 h-3.5"></i>
                        Riders <span class="opacity-70">(<?php echo $totalRiders; ?>)</span>
                    </a>
                </div>

                <!-- Desktop table -->
                <div class="hidden md:block bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-6 md:p-8 w-full transition-colors">
                    <table class="w-full text-left whitespace-nowrap min-w-[700px]">
                        <thead>
                            <tr class="border-b border-card-border dark:border-dark-border">
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">USER</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">EMAIL</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">ROLE</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">STATUS</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em] text-right">JOINED</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em] text-right">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-card-border dark:divide-dark-border">
                            <?php if (empty($users)): ?>
                                <tr><td colspan="6" class="py-10 text-center text-gray-400 font-medium">No users found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition-colors group">
                                    <td class="py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full overflow-hidden bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border flex-shrink-0">
                                                <?php if (!empty($u['profile_pic']) && file_exists('../' . $u['profile_pic'])): ?>
                                                    <img src="../<?php echo $u['profile_pic']; ?>" alt="" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['full_name']); ?>&background=FF6B35&color=fff&size=40&font-size=0.4" alt="" class="w-full h-full object-cover">
                                                <?php endif; ?>
                                            </div>
                                            <span class="font-bold text-gray-900 dark:text-white text-sm transition-colors"><?php echo sanitize($u['full_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-5 text-gray-500 dark:text-gray-400 font-medium text-sm transition-colors"><?php echo sanitize($u['email'] ?? 'N/A'); ?></td>
                                    <td class="py-5">
                                        <?php if ($u['role'] === 'delivery'): ?>
                                            <span class="px-3 py-1.5 border border-purple-200 rounded-md text-[10px] font-bold bg-purple-50 text-purple-600 uppercase tracking-[0.1em] flex items-center gap-1 w-max">
                                                <i data-lucide="bike" class="w-3 h-3"></i> Rider
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1.5 border border-blue-200 rounded-md text-[10px] font-bold bg-blue-50 text-blue-600 uppercase tracking-[0.1em] flex items-center gap-1 w-max">
                                                <i data-lucide="shopping-bag" class="w-3 h-3"></i> Customer
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-5">
                                        <span class="px-3 py-1.5 rounded-md text-[10px] font-bold tracking-[0.1em] uppercase border <?php echo $u['status'] === 'active' ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200'; ?>">
                                            <?php echo ucfirst($u['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-5 text-right text-gray-400 text-xs font-medium"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                    <td class="py-5 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="view_user.php?id=<?php echo $u['id']; ?>" class="p-2 text-gray-400 hover:text-primary transition-colors bg-gray-50 dark:bg-dark-bg rounded-lg border border-transparent hover:border-primary/20 hover:bg-primary/5">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile cards -->
                <div class="md:hidden space-y-4">
                <?php if (empty($users)): ?>
                    <div class="text-center py-10 text-gray-400 font-medium">No users found.</div>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <div class="bg-white dark:bg-dark-card rounded-2xl border border-card-border dark:border-dark-border p-4 shadow-sm transition-colors">
                        <!-- Top row: avatar + name/email + role badge -->
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full overflow-hidden bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border flex-shrink-0">
                                <?php if (!empty($u['profile_pic']) && file_exists('../' . $u['profile_pic'])): ?>
                                    <img src="../<?php echo $u['profile_pic']; ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['full_name']); ?>&background=FF6B35&color=fff&size=48&font-size=0.4" alt="" class="w-full h-full object-cover">
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-900 dark:text-white text-sm truncate transition-colors"><?php echo sanitize($u['full_name']); ?></p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 font-medium truncate transition-colors"><?php echo sanitize($u['email'] ?? 'N/A'); ?></p>
                                <p class="text-[10px] text-gray-300 dark:text-gray-600 font-medium mt-0.5 transition-colors"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></p>
                            </div>
                            <div class="flex-shrink-0 text-right space-y-1">
                                <?php if ($u['role'] === 'delivery'): ?>
                                    <span class="block px-2 py-1 border border-purple-200 rounded-lg text-[9px] font-bold bg-purple-50 text-purple-600 uppercase tracking-wider">Rider</span>
                                <?php else: ?>
                                    <span class="block px-2 py-1 border border-blue-200 rounded-lg text-[9px] font-bold bg-blue-50 text-blue-600 uppercase tracking-wider">Customer</span>
                                <?php endif; ?>
                                <span class="block px-2 py-1 rounded-lg text-[9px] font-bold uppercase tracking-wider border <?php echo $u['status'] === 'active' ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200'; ?>">
                                    <?php echo ucfirst($u['status']); ?>
                                </span>
                            </div>
                        </div>
                        <!-- Divider + view button -->
                        <div class="border-t border-card-border dark:border-dark-border mt-3 pt-3 flex justify-end transition-colors">
                            <a href="view_user.php?id=<?php echo $u['id']; ?>"
                               class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary/5 dark:bg-primary/10 border border-primary/20 text-primary text-xs font-bold hover:bg-primary hover:text-white transition-all">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i> View Profile
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <script> lucide.createIcons(); </script>
    <?php include 'includes/bottom_nav.php'; ?>
</body>
</html>
