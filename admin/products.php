<?php
// admin/products.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Simple Admin Check
if (!is_admin()) {
    $_SESSION['flash_message'] = "Access denied.";
    redirect('../login.php');
}

$products = [];
$categories = [];
try {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
    $products = $stmt->fetchAll();

    $cat_stmt = $pdo->query("SELECT * FROM categories");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback or Error display
}
?>
<head>
    <?php include 'includes/head.php'; ?>
    <title>Manage Products - FoodVerse</title>
</head>
<body class="bg-app-bg dark:bg-dark-bg min-h-screen text-gray-900 dark:text-gray-100 transition-colors">

    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-dark-card border-r border-card-border dark:border-dark-border hidden md:flex flex-col h-full z-10 flex-shrink-0 transition-colors">
            <!-- Logo area -->
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
                <a href="products.php" class="flex items-center gap-3 px-4 py-3 bg-primary/5 dark:bg-primary/10 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
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
            
            <!-- Topbar -->
            <?php include 'includes/topbar.php'; ?>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto p-4 md:p-8 w-full bg-app-bg dark:bg-dark-bg pb-28 md:pb-8 transition-colors">
                <?php display_flash_message(); ?>

                <!-- Page heading + Add button -->
                <div class="flex justify-between items-center mb-5">
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">Products</h2>
                        <p class="text-sm text-gray-400 mt-0.5 font-medium">Manage your menu offerings and stock.</p>
                    </div>
                    <button onclick="toggleModal()" class="bg-primary text-white px-4 py-2.5 rounded-xl font-bold shadow-sm hover:bg-primary-hover transition-all flex items-center gap-2 text-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">Add Product</span>
                        <span class="sm:hidden">Add</span>
                    </button>
                </div>

                <!-- Desktop table -->
                <div class="hidden md:block bg-white dark:bg-dark-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-card-border dark:border-dark-border p-6 md:p-8 w-full transition-colors">
                    <table class="w-full text-left whitespace-nowrap min-w-[700px]">
                        <thead>
                            <tr class="border-b border-card-border dark:border-dark-border">
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">Product</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">Category</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">Price</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em]">Stock</th>
                                <th class="pb-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-[0.15em] text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-card-border dark:divide-dark-border">
                            <?php foreach ($products as $p): ?>
                            <?php $display_stock = max(0, (int)$p['stock_quantity']); ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition-colors group">
                                <td class="py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 bg-white dark:bg-dark-bg rounded-xl overflow-hidden border border-card-border dark:border-dark-border shadow-sm flex-shrink-0 transition-colors">
                                            <img src="../<?php echo !empty($p['image_url']) ? $p['image_url'] : 'images/placeholder.png'; ?>" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white text-sm transition-colors"><?php echo sanitize($p['name']); ?></p>
                                            <?php if ($p['is_deal']): ?>
                                                <span class="text-[9px] bg-yellow-50 text-yellow-600 px-2 py-0.5 mt-0.5 block w-max rounded-full font-bold uppercase tracking-wider border border-yellow-200">Deal</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-5 text-gray-500 dark:text-gray-400 text-sm font-medium transition-colors"><?php echo sanitize($p['category_name']); ?></td>
                                <td class="py-5 font-black text-gray-900 dark:text-white transition-colors">Rs. <?php echo number_format($p['price'], 0); ?></td>
                                <td class="py-5">
                                    <span class="px-3 py-1 border <?php echo $display_stock < 10 ? 'bg-red-50 text-red-600 border-red-200' : 'bg-green-50 text-green-600 border-green-200'; ?> rounded-md text-[10px] tracking-[0.1em] font-bold uppercase">
                                        <?php echo $display_stock; ?> left
                                    </span>
                                </td>
                                <td class="py-5 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="p-2 text-gray-400 hover:text-primary transition-colors bg-gray-50 dark:bg-dark-bg rounded-lg border border-transparent hover:border-primary/20 hover:bg-primary/5">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $p['id']; ?>)" class="p-2 text-gray-400 hover:text-red-500 transition-colors bg-gray-50 dark:bg-dark-bg rounded-lg border border-transparent hover:border-red-200 hover:bg-red-50">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile cards -->
                <div class="md:hidden space-y-4">
                <?php if (empty($products)): ?>
                    <div class="text-center py-10 text-gray-400 font-medium">No products found.</div>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                    <?php $display_stock = max(0, (int)$p['stock_quantity']); ?>
                    <div class="bg-white dark:bg-dark-card rounded-2xl border border-card-border dark:border-dark-border p-5 shadow-sm transition-colors transition-all">
                        <!-- Top row: image + name + price -->
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 bg-white dark:bg-dark-bg rounded-xl overflow-hidden border border-card-border dark:border-dark-border shadow-sm flex-shrink-0 transition-colors">
                                <img src="../<?php echo !empty($p['image_url']) ? $p['image_url'] : 'images/placeholder.png'; ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-900 dark:text-white text-sm truncate transition-colors"><?php echo sanitize($p['name']); ?></p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 font-medium transition-colors"><?php echo sanitize($p['category_name']); ?></p>
                                <?php if ($p['is_deal']): ?>
                                    <span class="text-[9px] bg-yellow-50 text-yellow-600 px-2 py-0.5 mt-0.5 inline-block rounded-full font-bold uppercase tracking-wider border border-yellow-200">Deal</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="font-black text-primary text-base">Rs. <?php echo number_format($p['price'], 0); ?></div>
                            </div>
                        </div>
                        <!-- Divider -->
                        <div class="border-t border-card-border dark:border-dark-border my-3 transition-colors"></div>
                        <!-- Bottom row: stock + actions -->
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1.5 border <?php echo $display_stock < 10 ? 'bg-red-50 text-red-600 border-red-200' : 'bg-green-50 text-green-600 border-green-200'; ?> rounded-xl text-[10px] tracking-wider font-bold uppercase">
                                <?php echo $display_stock; ?> in stock
                            </span>
                            <div class="flex items-center gap-2">
                                <a href="edit_product.php?id=<?php echo $p['id']; ?>"
                                   class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border text-gray-400 dark:text-gray-500 hover:text-primary hover:border-primary/30 hover:bg-primary/5 transition-all transition-colors">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </a>
                                <button onclick="confirmDelete(<?php echo $p['id']; ?>)"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border text-gray-400 dark:text-gray-500 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition-all transition-colors">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal fixed w-full h-full top-0 left-0 flex items-center justify-center opacity-0 pointer-events-none z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900/60 backdrop-blur-sm transition-all" onclick="toggleModal()"></div>
        
        <div class="modal-container bg-white dark:bg-dark-card w-full max-w-lg mx-auto rounded-3xl shadow-2xl z-50 overflow-y-auto max-h-[90vh] border border-card-border dark:border-dark-border transition-colors">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6 border-b border-card-border dark:border-dark-border pb-4">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Add New Product</h3>
                    <button onclick="toggleModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 bg-gray-50 dark:bg-dark-bg p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form action="actions/add_product.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                    <div class="space-y-2">
                        <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500">Product Name</label>
                        <input type="text" name="name" required placeholder="e.g. Cheese Burger"
                            class="w-full bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white dark:focus:bg-dark-bg transition-all text-gray-900 dark:text-white font-medium">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500">Category</label>
                            <select name="category_id" required
                                class="w-full bg-section-bg dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white dark:focus:bg-dark-bg transition-all text-gray-900 dark:text-white font-medium">
                                <option value="" disabled selected>Select</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500">Price (Rs.)</label>
                            <input type="number" name="price" required placeholder="0"
                                class="w-full bg-section-bg border border-card-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white transition-all text-gray-900 font-medium">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500">Stock</label>
                            <input type="number" name="stock" required placeholder="0" min="0" step="1"
                                class="w-full bg-section-bg border border-card-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white transition-all text-gray-900 font-medium">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500">Veg Type</label>
                            <select name="veg_type" required
                                class="w-full bg-section-bg border border-card-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white transition-all text-gray-900 font-medium">
                                <option value="veg">Veg</option>
                                <option value="non-veg">Non-Veg</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500">Image</label>
                        <input type="file" name="image" accept="image/*"
                            class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:uppercase file:tracking-wider file:bg-primary/10 file:text-primary hover:file:bg-primary hover:file:text-white cursor-pointer transition-all">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] tracking-widest uppercase font-bold text-gray-500">Description</label>
                        <textarea name="description" rows="3" placeholder="Describe the product..."
                            class="w-full bg-section-bg border border-card-border rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary/40 focus:bg-white transition-all text-gray-900 font-medium resize-none"></textarea>
                    </div>

                    <div class="flex items-center gap-3 bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <input type="checkbox" name="is_deal" id="is_deal_modal" class="w-4 h-4 rounded text-primary border-gray-300 focus:ring-primary">
                        <label for="is_deal_modal" class="text-sm font-bold text-gray-900 cursor-pointer">Mark as Deal of the Day</label>
                    </div>

                    <button type="submit" class="w-full bg-primary text-white py-4 rounded-xl font-bold hover:bg-primary-hover transition-all active:scale-[0.98] uppercase tracking-wider text-sm shadow-sm">
                        Create Product
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function toggleModal() {
            const body = document.querySelector('body');
            const modal = document.querySelector('#addProductModal');
            modal.classList.toggle('opacity-0');
            modal.classList.toggle('pointer-events-none');
            body.classList.toggle('modal-active');
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                window.location.href = `actions/delete_product.php?id=${id}`;
            }
        }
    </script>
    <?php include 'includes/bottom_nav.php'; ?>
</body>
</html>
