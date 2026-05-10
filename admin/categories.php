<?php
// admin/categories.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_admin()) redirect('../login.php');

$categories = [];

try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback
}
?>
<head>
    <?php include 'includes/head.php'; ?>
    <title>Manage Categories - FoodVerse</title>
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
                <a href="categories.php" class="flex items-center gap-3 px-4 py-3 bg-primary/5 dark:bg-primary/10 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
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

                <!-- Page heading + Add button -->
                <div class="flex justify-between items-center mb-5">
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">Categories</h2>
                        <p class="text-sm text-gray-400 mt-0.5 font-medium">Add, delete and manage menu classifications.</p>
                    </div>
                    <button onclick="toggleModal('addCategoryModal')" class="bg-primary text-white px-4 py-2.5 rounded-xl font-bold shadow-sm hover:bg-primary-hover transition-all flex items-center gap-2 text-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">Add Category</span>
                        <span class="sm:hidden">Add</span>
                    </button>
                </div>

                <!-- Desktop table -->
                <div class="hidden md:block bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-6 md:p-8 w-full transition-colors">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-card-border dark:border-dark-border">
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">ID</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">Category Name</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em] text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-card-border dark:divide-dark-border">
                            <?php if(empty($categories)): ?>
                                <tr><td colspan="3" class="py-10 text-center text-gray-400 font-medium">No categories found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition-colors group">
                                    <td class="py-5"><span class="font-bold text-primary text-sm">#<?php echo $cat['id']; ?></span></td>
                                    <td class="py-5"><div class="font-bold text-gray-900 dark:text-white text-sm transition-colors"><?php echo sanitize($cat['name']); ?></div></td>
                                    <td class="py-5 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo addslashes(sanitize($cat['name'])); ?>')" class="p-2 text-gray-400 hover:text-primary transition-colors bg-gray-50 dark:bg-dark-bg rounded-lg border border-transparent hover:border-primary/20 hover:bg-primary/5">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="confirmDelete(<?php echo $cat['id']; ?>)" class="p-2 text-gray-400 hover:text-red-500 transition-colors bg-gray-50 dark:bg-dark-bg rounded-lg border border-transparent hover:border-red-200 hover:bg-red-50">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile cards -->
                <div class="md:hidden space-y-3">
                <?php if(empty($categories)): ?>
                    <div class="text-center py-10 text-gray-400 font-medium">No categories found.</div>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <div class="bg-white dark:bg-dark-card rounded-2xl border border-card-border dark:border-dark-border p-4 shadow-sm flex items-center gap-3 transition-colors">
                        <!-- Icon -->
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="tags" class="w-5 h-5 text-primary"></i>
                        </div>
                        <!-- Name -->
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 dark:text-white text-sm truncate transition-colors"><?php echo sanitize($cat['name']); ?></p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium transition-colors">#<?php echo $cat['id']; ?></p>
                        </div>
                        <!-- Actions -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo addslashes(sanitize($cat['name'])); ?>')"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border text-gray-400 dark:text-gray-500 hover:text-primary hover:border-primary/30 hover:bg-primary/5 transition-all transition-colors">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button onclick="confirmDelete(<?php echo $cat['id']; ?>)"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border text-gray-400 dark:text-gray-500 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition-all transition-colors">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="modal fixed w-full h-full top-0 left-0 flex items-center justify-center opacity-0 pointer-events-none z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900/60 backdrop-blur-sm transition-all" onclick="toggleModal('addCategoryModal')"></div>
        <div class="modal-container bg-white dark:bg-dark-card w-full max-w-md mx-auto rounded-3xl shadow-2xl z-50 border border-card-border dark:border-dark-border transition-colors">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6 border-b border-card-border dark:border-dark-border pb-4">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Add Category</h3>
                    <button onclick="toggleModal('addCategoryModal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 bg-gray-50 dark:bg-dark-bg p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <form action="actions/add_category.php" method="POST" class="space-y-5">
                    <div class="space-y-2">
                        <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500 dark:text-gray-400 transition-colors">Category Name</label>
                        <input type="text" name="name" required placeholder="e.g. Beverages"
                            class="w-full bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white dark:focus:bg-dark-bg transition-all text-gray-900 dark:text-white font-medium">
                    </div>
                    <button type="submit" class="w-full bg-primary text-white py-4 rounded-xl font-bold hover:bg-primary-hover transition-all active:scale-[0.98] uppercase tracking-wider text-sm shadow-sm">
                        Create Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="modal fixed w-full h-full top-0 left-0 flex items-center justify-center opacity-0 pointer-events-none z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900/60 backdrop-blur-sm transition-all" onclick="toggleModal('editCategoryModal')"></div>
        <div class="modal-container bg-white dark:bg-dark-card w-full max-w-md mx-auto rounded-3xl shadow-2xl z-50 border border-card-border dark:border-dark-border transition-colors">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6 border-b border-card-border dark:border-dark-border pb-4 transition-colors">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight transition-colors">Edit Category</h3>
                    <button onclick="toggleModal('editCategoryModal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 bg-gray-50 dark:bg-dark-bg p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-all transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <form action="actions/edit_category.php" method="POST" class="space-y-5">
                    <input type="hidden" name="id" id="edit_cat_id">
                    <div class="space-y-2">
                        <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500 dark:text-gray-400 transition-colors">Category Name</label>
                        <input type="text" name="name" id="edit_cat_name" required
                            class="w-full bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white dark:focus:bg-dark-bg transition-all text-gray-900 dark:text-white font-medium transition-colors">
                    </div>
                    <button type="submit" class="w-full bg-primary text-white py-4 rounded-xl font-bold hover:bg-primary-hover transition-all active:scale-[0.98] uppercase tracking-wider text-sm shadow-sm">
                        Update Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function toggleModal(modalId) {
            const body = document.querySelector('body');
            const modal = document.querySelector('#' + modalId);
            modal.classList.toggle('opacity-0');
            modal.classList.toggle('pointer-events-none');
            body.classList.toggle('modal-active');
        }

        function editCategory(id, name) {
            document.getElementById('edit_cat_id').value = id;
            document.getElementById('edit_cat_name').value = name;
            toggleModal('editCategoryModal');
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this category? Associated products may be affected.')) {
                window.location.href = `actions/delete_category.php?id=${id}`;
            }
        }
    </script>
    <?php include 'includes/bottom_nav.php'; ?>
</body>
</html>
