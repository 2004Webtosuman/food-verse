<?php
// admin/includes/bottom_nav.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Admin Bottom Navigation -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-card-border z-50 shadow-[0_-4px_20px_rgba(0,0,0,0.06)]">
    <div class="grid grid-cols-6 items-center px-2 py-2">
        <a href="dashboard.php" class="flex flex-col items-center gap-1 py-1 <?php echo $current_page == 'dashboard.php' ? 'text-primary' : 'text-gray-400'; ?> transition-all">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            <span class="text-[9px] font-bold uppercase tracking-wide leading-none">Dashboard</span>
        </a>
        <a href="products.php" class="flex flex-col items-center gap-1 py-1 <?php echo $current_page == 'products.php' ? 'text-primary' : 'text-gray-400'; ?> transition-all">
            <i data-lucide="package" class="w-5 h-5"></i>
            <span class="text-[9px] font-bold uppercase tracking-wide leading-none">Products</span>
        </a>
        <a href="orders.php" class="flex flex-col items-center gap-1 py-1 <?php echo $current_page == 'orders.php' ? 'text-primary' : 'text-gray-400'; ?> transition-all">
            <i data-lucide="shopping-bag" class="w-5 h-5"></i>
            <span class="text-[9px] font-bold uppercase tracking-wide leading-none">Orders</span>
        </a>
        <a href="users.php" class="flex flex-col items-center gap-1 py-1 <?php echo $current_page == 'users.php' ? 'text-primary' : 'text-gray-400'; ?> transition-all">
            <i data-lucide="users" class="w-5 h-5"></i>
            <span class="text-[9px] font-bold uppercase tracking-wide leading-none">Users</span>
        </a>
        <a href="categories.php" class="flex flex-col items-center gap-1 py-1 <?php echo $current_page == 'categories.php' ? 'text-primary' : 'text-gray-400'; ?> transition-all">
            <i data-lucide="tags" class="w-5 h-5"></i>
            <span class="text-[9px] font-bold uppercase tracking-wide leading-none">Category</span>
        </a>
        <a href="reports.php" class="flex flex-col items-center gap-1 py-1 <?php echo $current_page == 'reports.php' ? 'text-primary' : 'text-gray-400'; ?> transition-all">
            <i data-lucide="bar-chart-2" class="w-5 h-5"></i>
            <span class="text-[9px] font-bold uppercase tracking-wide leading-none">Reports</span>
        </a>
    </div>
</nav>

<script>
    if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
